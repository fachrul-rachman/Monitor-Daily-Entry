<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\Finding;
use App\Models\Holiday;
use App\Models\HealthScore;
use App\Models\LeaveRequest;
use App\Models\ReportSetting;
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

        $setting = ReportSetting::current();
        $now = Carbon::now();

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

        DB::transaction(function () use ($users, $workdays, $setting, $now): void {
            $dateStrings = array_map(fn (Carbon $d) => $d->toDateString(), $workdays);

            $userIds = $users->pluck('id')->map(fn ($v) => (int) $v)->values()->all();

            // Approved day off map: user_id + date => true (only for workdays range).
            $offByUser = [];
            $rangeFrom = $dateStrings[0] ?? Carbon::today()->toDateString();
            $rangeTo = $dateStrings ? $dateStrings[count($dateStrings) - 1] : Carbon::today()->toDateString();
            $approvedLeaves = LeaveRequest::query()
                ->where('status', 'approved')
                ->whereIn('user_id', $userIds)
                ->whereDate('start_date', '<=', $rangeTo)
                ->whereDate('end_date', '>=', $rangeFrom)
                ->get(['user_id', 'start_date', 'end_date']);

            if ($approvedLeaves->isNotEmpty()) {
                $workdaySet = array_fill_keys($dateStrings, true);
                foreach ($approvedLeaves as $lr) {
                    $uid = (int) $lr->user_id;
                    if (! $lr->start_date || ! $lr->end_date) {
                        continue;
                    }
                    $cursor = Carbon::parse($lr->start_date)->startOfDay();
                    $end = Carbon::parse($lr->end_date)->startOfDay();
                    while ($cursor->lte($end)) {
                        $k = $cursor->toDateString();
                        if (isset($workdaySet[$k])) {
                            $offByUser[$uid][$k] = true;
                        }
                        $cursor->addDay();
                    }
                }
            }

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
                ->whereIn('score_date', $dateStrings)
                ->whereIn('scope_type', ['company', 'division', 'user'])
                ->delete();

            foreach ($users as $user) {
                $userOffSet = $offByUser[(int) $user->id] ?? [];
                $this->computeFindingsForUser($user, $workdays, $now, $setting, $userOffSet);
            }

            $this->computeScores($users, $workdays, $now, $setting);
        });
    }

    private function workdaysBetween(Carbon $from, Carbon $to): array
    {
        $holidayDates = Holiday::query()
            ->whereBetween('holiday_date', [$from->toDateString(), $to->toDateString()])
            ->where('is_holiday', true)
            ->where('is_joint_holiday', false)
            ->pluck('holiday_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
        $holidaySet = array_fill_keys($holidayDates, true);

        $days = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            if (! $cursor->isWeekend()) {
                $key = $cursor->toDateString();
                if (! isset($holidaySet[$key])) {
                    $days[] = $cursor->copy();
                }
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function computeFindingsForUser(User $user, array $workdays, Carbon $now, ReportSetting $setting, array $offDateSet = []): void
    {
        $dateStrings = array_map(fn (Carbon $d) => $d->toDateString(), $workdays);

        $entries = DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('entry_date', $dateStrings)
            ->get(['id', 'entry_date', 'plan_status', 'realization_status', 'plan_submitted_at', 'realization_submitted_at'])
            ->keyBy(fn (DailyEntry $e) => $e->entry_date->toDateString());

        // Missing daily (per hari)
        foreach ($workdays as $d) {
            $key = $d->toDateString();

            // Day off: tidak dianggap missing dan tidak dibuat temuan.
            if (isset($offDateSet[$key])) {
                continue;
            }

            /** @var DailyEntry|null $entry */
            $entry = $entries->get($key);

            // Missing baru dihitung setelah melewati jam tutup (supaya angka "hari ini" tidak menakutkan).
            $isToday = $key === $now->toDateString();
            $planClose = Carbon::parse($key.' '.$setting->plan_close_time);
            $realClose = Carbon::parse($key.' '.$setting->realization_close_time);

            $shouldEvaluatePlan = ! $isToday || $now->gt($planClose);
            $shouldEvaluateReal = ! $isToday || $now->gt($realClose);

            $planSubmitted = (bool) ($entry && $entry->plan_submitted_at);
            $realSubmitted = (bool) ($entry && $entry->realization_submitted_at);

            $missingPlan = $shouldEvaluatePlan && ! $planSubmitted;
            $missingReal = $shouldEvaluateReal && ! $realSubmitted;

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
            $weekEndDate = Carbon::parse($weekStart)->endOfWeek(Carbon::SUNDAY);
            if ($weekEndDate->gt($now)) {
                // Minggu berjalan belum selesai, jangan bikin finding "mingguan" dulu.
                continue;
            }

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
            $weekEnd = $weekEndDate->toDateString();

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
            $endDateObj = $lastFive->last();
            $endDateKey = $endDateObj->toDateString();
            $endRealClose = Carbon::parse($endDateKey.' '.$setting->realization_close_time);
            if ($endDateKey === $now->toDateString() && $now->lt($endRealClose)) {
                // Hari ini belum selesai (realisasi belum tutup), jangan nilai repetitif dulu.
                return;
            }

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
                $endDate = $endDateKey;

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

    private function computeScores($users, array $workdays, Carbon $now, ReportSetting $setting): void
    {
        $byDivision = collect($users)->groupBy('division_id');

        foreach ($workdays as $day) {
            $scoreDate = $day->toDateString();

            // Score user (per hari)
            $userScores = [];
            foreach ($users as $user) {
                $userScores[$user->id] = $this->scoreUserForDay($user->id, $day, $now, $setting);

                HealthScore::query()->create([
                    'score_date' => $scoreDate,
                    'scope_type' => 'user',
                    'scope_id' => $user->id,
                    'score' => $userScores[$user->id]['score'],
                    'components' => $userScores[$user->id],
                ]);
            }

            // Score division (rata-rata score user hari itu)
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
    }

    /**
     * Komposisi score MVP per hari (mudah dipahami untuk chart):
     * - Progress Big Rock/Roadmap (terberat)
     * - Findings (medium/high)
     * - Missing
     * - On-time
     */
    private function scoreUserForDay(int $userId, Carbon $day, Carbon $now, ReportSetting $setting): array
    {
        $date = $day->toDateString();
        $isToday = $date === $now->toDateString();

        /** @var DailyEntry|null $entry */
        $entry = DailyEntry::query()
            ->where('user_id', $userId)
            ->whereDate('entry_date', $date)
            ->first(['id', 'plan_status', 'realization_status', 'plan_submitted_at', 'realization_submitted_at']);

        $planClose = Carbon::parse($date.' '.$setting->plan_close_time);
        $realClose = Carbon::parse($date.' '.$setting->realization_close_time);

        $planSubmitted = (bool) ($entry && $entry->plan_submitted_at);
        $realSubmitted = (bool) ($entry && $entry->realization_submitted_at);

        $missingPlan = (! $isToday || $now->gt($planClose)) && ! $planSubmitted;
        $missingReal = (! $isToday || $now->gt($realClose)) && ! $realSubmitted;
        $missing = $missingPlan || $missingReal;

        $late = (bool) ($entry && ($entry->plan_status === 'late' || $entry->realization_status === 'late'));
        $onTime = (bool) ($entry && $planSubmitted && $realSubmitted && ! $missing && ! $late);

        // Progress harian: apakah roadmap disentuh hari ini (indikasi “ada gerak”).
        $totalRoadmaps = RoadmapItem::query()
            ->whereIn('big_rock_id', function ($q) use ($userId) {
                $q->select('id')->from('big_rocks')->where('user_id', $userId)->where('status', 'active');
            })
            ->count();

        $workedRoadmaps = 0;
        $workedBigRocks = 0;

        if ($entry) {
            $items = $entry->items()->get(['big_rock_id', 'roadmap_item_id', 'realization_status']);
            foreach ($items as $it) {
                if ($it->big_rock_id) {
                    $workedBigRocks++;
                }
                if ($it->roadmap_item_id && in_array($it->realization_status, ['done', 'partial'], true)) {
                    $workedRoadmaps++;
                }
            }
        }

        $progressRatio = $totalRoadmaps > 0 ? min(1, $workedRoadmaps / $totalRoadmaps) : ($workedBigRocks > 0 ? 0.5 : 0);
        $progressScore = (int) round($progressRatio * 100);

        $ontimeRate = $onTime ? 100 : 0;
        $missingPenalty = $missing ? 30 : 0;
        $latePenalty = $late ? 15 : 0;

        // Findings penalty (daily): high=15, medium=8, low=3 (cap 40)
        $findingCounts = Finding::query()
            ->whereDate('finding_date', $date)
            ->where('scope_type', 'user')
            ->where('scope_id', $userId)
            ->selectRaw("sum(case when severity='high' then 1 else 0 end) as high_count")
            ->selectRaw("sum(case when severity='medium' then 1 else 0 end) as medium_count")
            ->selectRaw("sum(case when severity='low' then 1 else 0 end) as low_count")
            ->first();

        $highCount = (int) ($findingCounts?->high_count ?? 0);
        $mediumCount = (int) ($findingCounts?->medium_count ?? 0);
        $lowCount = (int) ($findingCounts?->low_count ?? 0);
        $findingPenalty = min(40, ($highCount * 15) + ($mediumCount * 8) + ($lowCount * 3));

        // Bobot sesuai prioritas yang kamu berikan (progress > findings > missing > ontime).
        $score = (int) round(
            (0.45 * $progressScore)
            + (0.20 * (100 - $findingPenalty))
            + (0.20 * (100 - $missingPenalty))
            + (0.15 * $ontimeRate)
            - ($latePenalty / 5)
        );

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'progress_score' => $progressScore,
            'ontime_rate' => $ontimeRate,
            'missing' => $missing,
            'late' => $late,
            'findings_high' => $highCount,
            'findings_medium' => $mediumCount,
            'findings_low' => $lowCount,
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
