<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\Finding;
use App\Models\HealthScore;
use App\Models\RoadmapItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    /**
     * Hitung findings + health score untuk rentang tanggal tertentu (MVP: Senin–Jumat).
     * Hasilnya disimpan ke DB agar halaman Director/HoD bisa menarik ringkasan dengan cepat.
     */
    public function compute(Carbon $from, Carbon $to): void
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $users = User::query()
            ->whereIn('role', ['hod', 'manager'])
            ->where('status', 'active')
            ->get(['id', 'division_id', 'name', 'role']);

        if ($users->isEmpty()) {
            return;
        }

        $workdays = $this->workdaysBetween($from, $to);
        if (empty($workdays)) {
            return;
        }

        DB::transaction(function () use ($users, $workdays): void {
            $dateStrings = array_map(fn (Carbon $d) => $d->toDateString(), $workdays);

            // Bersihkan data lama di rentang ini (idempotent).
            Finding::query()
                ->whereIn('finding_date', $dateStrings)
                ->whereIn('type', ['missing_daily', 'repetitive_5days'])
                ->delete();

            // Finding weekly (pakai tanggal akhir minggu)
            $weekEndDates = collect($workdays)
                ->map(fn (Carbon $d) => $d->copy()->endOfWeek(Carbon::SUNDAY)->toDateString())
                ->unique()
                ->values()
                ->all();

            Finding::query()
                ->whereIn('finding_date', $weekEndDates)
                ->where('type', 'late_weekly')
                ->delete();

            HealthScore::query()
                ->whereIn('score_date', [$dateStrings[array_key_last($dateStrings)]])
                ->whereIn('scope_type', ['company', 'division', 'user'])
                ->delete();

            foreach ($users as $user) {
                $this->computeFindingsForUser($user, $workdays);
            }

            $this->computeScores($users, $workdays);
        });
    }

    private function workdaysBetween(Carbon $from, Carbon $to): array
    {
        $days = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            if (! $cursor->isWeekend()) {
                $days[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function computeFindingsForUser(User $user, array $workdays): void
    {
        $dateStrings = array_map(fn (Carbon $d) => $d->toDateString(), $workdays);

        $entries = DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('entry_date', $dateStrings)
            ->get(['id', 'entry_date', 'plan_status', 'realization_status'])
            ->keyBy(fn (DailyEntry $e) => $e->entry_date->toDateString());

        // Missing daily (per hari)
        foreach ($workdays as $d) {
            $key = $d->toDateString();
            /** @var DailyEntry|null $entry */
            $entry = $entries->get($key);

            $missingPlan = ! $entry || $entry->plan_status === 'missing';
            $missingReal = ! $entry || $entry->realization_status === 'missing';

            if (! $missingPlan && ! $missingReal) {
                continue;
            }

            $parts = [];
            if ($missingPlan) {
                $parts[] = 'plan';
            }
            if ($missingReal) {
                $parts[] = 'realisasi';
            }

            Finding::query()->create([
                'finding_date' => $key,
                'scope_type' => 'user',
                'scope_id' => $user->id,
                'user_id' => $user->id,
                'division_id' => $user->division_id,
                'type' => 'missing_daily',
                'severity' => 'medium',
                'title' => 'Laporan harian tidak lengkap',
                'description' => 'Sistem mendeteksi bagian yang belum terisi: '.implode(' & ', $parts).'.',
                'meta' => [
                    'missing' => $parts,
                ],
            ]);
        }

        // Telat mingguan (threshold: >2 medium, >3 high)
        $byWeek = collect($workdays)->groupBy(fn (Carbon $d) => $d->copy()->startOfWeek(Carbon::MONDAY)->toDateString());
        foreach ($byWeek as $weekStart => $daysInWeek) {
            $lateDays = 0;

            foreach ($daysInWeek as $d) {
                $key = $d->toDateString();
                /** @var DailyEntry|null $entry */
                $entry = $entries->get($key);
                if (! $entry) {
                    continue;
                }

                $isLate = $entry->plan_status === 'late' || $entry->realization_status === 'late';
                if ($isLate) {
                    $lateDays++;
                }
            }

            if ($lateDays <= 2) {
                continue;
            }

            $severity = $lateDays > 3 ? 'high' : 'medium';
            $weekEnd = Carbon::parse($weekStart)->endOfWeek(Carbon::SUNDAY)->toDateString();

            Finding::query()->create([
                'finding_date' => $weekEnd,
                'scope_type' => 'user',
                'scope_id' => $user->id,
                'user_id' => $user->id,
                'division_id' => $user->division_id,
                'type' => 'late_weekly',
                'severity' => $severity,
                'title' => 'Keterlambatan berulang dalam 1 minggu',
                'description' => "Terdeteksi telat {$lateDays} hari dalam minggu ini.",
                'meta' => [
                    'week_start' => $weekStart,
                    'late_days' => $lateDays,
                ],
            ]);
        }

        // Pola repetitif 5 hari kerja terakhir (indikasi “copas” sederhana untuk MVP)
        $lastFive = collect($workdays)->take(-5)->values();
        if ($lastFive->count() === 5) {
            $titles = [];
            foreach ($lastFive as $d) {
                $key = $d->toDateString();
                /** @var DailyEntry|null $entry */
                $entry = $entries->get($key);
                if (! $entry) {
                    return; // kalau ada hari kosong, kita tidak nilai copas
                }

                $firstItem = $entry->items()
                    ->orderBy('id')
                    ->first(['plan_title']);

                $titles[] = $this->normalizeText($firstItem?->plan_title ?? '');
            }

            $unique = collect($titles)->filter()->unique()->values();
            if ($unique->count() === 1) {
                $endDate = $lastFive->last()->toDateString();

                Finding::query()->create([
                    'finding_date' => $endDate,
                    'scope_type' => 'user',
                    'scope_id' => $user->id,
                    'user_id' => $user->id,
                    'division_id' => $user->division_id,
                    'type' => 'repetitive_5days',
                    'severity' => 'medium',
                    'title' => 'Rencana terlihat sama 5 hari berturut-turut',
                    'description' => 'Sistem melihat judul rencana yang sangat mirip selama 5 hari kerja.',
                    'meta' => [
                        'normalized_title' => $unique->first(),
                    ],
                ]);
            }
        }
    }

    private function computeScores($users, array $workdays): void
    {
        $scoreDate = $workdays[array_key_last($workdays)]->toDateString();

        // Score user (1 angka ringkas untuk periode ini).
        $userScores = [];
        foreach ($users as $user) {
            $userScores[$user->id] = $this->scoreUserForPeriod($user->id, $workdays);

            HealthScore::query()->create([
                'score_date' => $scoreDate,
                'scope_type' => 'user',
                'scope_id' => $user->id,
                'score' => $userScores[$user->id]['score'],
                'components' => $userScores[$user->id],
            ]);
        }

        // Score division (rata-rata score user di divisi)
        $byDivision = collect($users)->groupBy('division_id');
        foreach ($byDivision as $divisionId => $divisionUsers) {
            if (! $divisionId) {
                continue;
            }

            $scores = $divisionUsers->map(fn ($u) => $userScores[$u->id]['score'] ?? null)->filter();
            if ($scores->isEmpty()) {
                continue;
            }

            $avg = (int) round($scores->avg());
            HealthScore::query()->create([
                'score_date' => $scoreDate,
                'scope_type' => 'division',
                'scope_id' => (int) $divisionId,
                'score' => $avg,
                'components' => [
                    'avg_of_users' => true,
                    'users_count' => $scores->count(),
                ],
            ]);
        }

        // Score company (rata-rata semua)
        $all = collect($userScores)->pluck('score')->filter();
        if ($all->isNotEmpty()) {
            HealthScore::query()->create([
                'score_date' => $scoreDate,
                'scope_type' => 'company',
                'scope_id' => null,
                'score' => (int) round($all->avg()),
                'components' => [
                    'avg_of_users' => true,
                    'users_count' => $all->count(),
                ],
            ]);
        }
    }

    /**
     * Komposisi score MVP (bisa disesuaikan nanti via settings):
     * - Progress Big Rock/Roadmap (terberat)
     * - Findings
     * - Missing
     * - On-time
     */
    private function scoreUserForPeriod(int $userId, array $workdays): array
    {
        $dateStrings = array_map(fn (Carbon $d) => $d->toDateString(), $workdays);

        $entries = DailyEntry::query()
            ->where('user_id', $userId)
            ->whereIn('entry_date', $dateStrings)
            ->get(['id', 'entry_date', 'plan_status', 'realization_status'])
            ->keyBy(fn (DailyEntry $e) => $e->entry_date->toDateString());

        $missingDays = 0;
        $lateDays = 0;
        $ontimeDays = 0;

        $workedRoadmapIds = [];
        $workedBigRockIds = [];

        foreach ($workdays as $d) {
            $key = $d->toDateString();
            /** @var DailyEntry|null $entry */
            $entry = $entries->get($key);

            if (! $entry || $entry->plan_status === 'missing' || $entry->realization_status === 'missing') {
                $missingDays++;
                continue;
            }

            $isLate = $entry->plan_status === 'late' || $entry->realization_status === 'late';
            if ($isLate) {
                $lateDays++;
            } else {
                $ontimeDays++;
            }

            $items = $entry->items()->get(['big_rock_id', 'roadmap_item_id', 'realization_status']);
            foreach ($items as $it) {
                if ($it->big_rock_id) {
                    $workedBigRockIds[$it->big_rock_id] = true;
                }
                if ($it->roadmap_item_id && in_array($it->realization_status, ['done', 'partial'], true)) {
                    $workedRoadmapIds[$it->roadmap_item_id] = true;
                }
            }
        }

        $totalDays = max(count($workdays), 1);
        $ontimeRate = (int) round(($ontimeDays / $totalDays) * 100);

        // Progress: seberapa banyak roadmap yang tersentuh di periode ini (indikasi “roadmap bergerak”).
        $totalRoadmaps = RoadmapItem::query()
            ->whereIn('big_rock_id', function ($q) use ($userId) {
                $q->select('id')->from('big_rocks')->where('user_id', $userId)->where('status', 'active');
            })
            ->count();

        $workedRoadmaps = count($workedRoadmapIds);
        $progressRatio = $totalRoadmaps > 0 ? min(1, $workedRoadmaps / $totalRoadmaps) : (count($workedBigRockIds) > 0 ? 0.5 : 0);
        $progressScore = (int) round($progressRatio * 100);

        // Findings (penalty) — sederhana untuk MVP, supaya mudah dipahami.
        // Missing dan late juga ikut menurunkan score, tapi terpisah komponen.
        $findingPenalty = 0;

        $missingPenalty = min(40, $missingDays * 10);
        $latePenalty = min(30, $lateDays * 8);

        // Bobot sesuai prioritas yang kamu berikan (progress > findings > missing > ontime).
        $score = (int) round(
            (0.45 * $progressScore)
            + (0.20 * (100 - $findingPenalty))
            + (0.20 * (100 - $missingPenalty))
            + (0.15 * $ontimeRate)
            - $latePenalty / 10 // late tetap terasa, tapi tidak terlalu “menghukum”
        );

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'progress_score' => $progressScore,
            'ontime_rate' => $ontimeRate,
            'missing_days' => $missingDays,
            'late_days' => $lateDays,
            'worked_roadmaps' => $workedRoadmaps,
            'total_roadmaps' => $totalRoadmaps,
        ];
    }

    private function normalizeText(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        $v = preg_replace('/[^\p{L}\p{N}\s]/u', '', $v) ?? $v;

        return trim($v);
    }
}

