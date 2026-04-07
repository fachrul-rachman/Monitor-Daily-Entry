<?php

namespace Database\Seeders;

use App\Models\BigRock;
use App\Models\DailyEntry;
use App\Models\DailyEntryItem;
use App\Models\ReportSetting;
use App\Models\RoadmapItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WeeklyDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureReportSettingExists();

        $users = User::query()
            ->whereIn('role', ['hod', 'manager'])
            ->where('status', 'active')
            ->get(['id', 'name', 'email', 'role']);

        if ($users->isEmpty()) {
            return;
        }

        // Range: 7 hari terakhir (tidak termasuk hari ini)
        $start = Carbon::today()->subDays(7)->startOfDay();
        $end = Carbon::yesterday()->endOfDay();

        $setting = ReportSetting::current();

        foreach ($users as $user) {
            $demo = $this->ensureBigRocksAndRoadmaps($user);
            $this->seedDailyEntriesForUser($user->id, $start, $end, $setting, $demo);
        }

        // Setelah data demo masuk, hitung metrics supaya Director/HoD langsung ada angka ringkas.
        app(\App\Services\MetricsService::class)->compute($start, $end);
    }

    private function ensureReportSettingExists(): void
    {
        // Jangan mengubah konfigurasi admin yang sudah ada.
        $hasActive = ReportSetting::query()->where('is_active', true)->exists();
        if ($hasActive) {
            return;
        }

        ReportSetting::query()->create([
            'plan_open_time' => '07:00',
            'plan_close_time' => '10:00',
            'realization_open_time' => '15:00',
            'realization_close_time' => '23:00',
            'effective_from' => Carbon::today(),
            'is_active' => true,
        ]);
    }

    /**
     * @return array{big_rocks: array<string, BigRock>, roadmaps: array<string, array<string, RoadmapItem>>}
     */
    private function ensureBigRocksAndRoadmaps(User $user): array
    {
        // Supaya gampang dicek “real”, setiap role dapat contoh Big Rock yang beda.
        $bigRockTitles = $user->role === 'manager'
            ? [
                'Perbaikan Kualitas Layanan Cabang',
                'Peningkatan Kecepatan Respons Customer',
            ]
            : [
                'Implementasi SOP Baru',
                'Peningkatan Disiplin Eksekusi Tim',
            ];

        $bigRocks = [];
        $roadmaps = [];

        foreach ($bigRockTitles as $title) {
            $bigRock = BigRock::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $title,
                ],
                [
                    'status' => 'active',
                    'start_date' => Carbon::today()->subMonths(1)->toDateString(),
                    'end_date' => Carbon::today()->addMonths(2)->toDateString(),
                    'description' => $user->role === 'manager'
                        ? 'Sasaran untuk memperbaiki kualitas layanan dan pengalaman pelanggan.'
                        : 'Sasaran untuk memastikan cara kerja tim lebih rapi, konsisten, dan terukur.',
                ],
            );

            $bigRocks[$title] = $bigRock;

            $roadmapTitles = match ($title) {
                'Perbaikan Kualitas Layanan Cabang' => [
                    'Survey Kepuasan Pelanggan',
                    'Perbaikan Proses Antrian',
                    'Pelatihan Frontliner',
                ],
                'Peningkatan Kecepatan Respons Customer' => [
                    'Pemetaan bottleneck response',
                    'Standarisasi template balasan',
                    'Monitoring SLA harian',
                ],
                'Implementasi SOP Baru' => [
                    'Audit Proses Existing',
                    'Sosialisasi SOP',
                    'Uji coba dan evaluasi',
                ],
                default => [
                    'Atur ritme meeting mingguan',
                    'Review progress Big Rock',
                    'Coaching 1-on-1',
                ],
            };

            $roadmaps[$title] = [];

            foreach ($roadmapTitles as $index => $rmTitle) {
                $rm = RoadmapItem::query()->updateOrCreate(
                    [
                        'big_rock_id' => $bigRock->id,
                        'title' => $rmTitle,
                    ],
                    [
                        'status' => 'planned',
                        'sort_order' => $index + 1,
                    ],
                );

                $roadmaps[$title][$rmTitle] = $rm;
            }
        }

        return [
            'big_rocks' => $bigRocks,
            'roadmaps' => $roadmaps,
        ];
    }

    /**
     * @param array{big_rocks: array<string, BigRock>, roadmaps: array<string, array<string, RoadmapItem>>} $demo
     */
    private function seedDailyEntriesForUser(
        int $userId,
        Carbon $start,
        Carbon $end,
        ReportSetting $setting,
        array $demo,
    ): void {
        // Seed dibuat seperti “kisah 1 minggu”, dengan campuran:
        // - on-time
        // - late
        // - missing
        // - pola repetitif (buat calon “copas 5 hari”)

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $days[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        // Ambil 5 hari kerja terakhir untuk pola repetitif.
        $lastFiveWorkdays = collect($days)->take(-5)->values();

        foreach ($days as $dayIndex => $date) {
            $entryDate = $date->toDateString();

            $existing = DailyEntry::query()
                ->where('user_id', $userId)
                ->whereDate('entry_date', $entryDate)
                ->first();

            if ($existing) {
                continue;
            }

            $planOpen = Carbon::parse($entryDate.' '.$setting->plan_open_time);
            $planClose = Carbon::parse($entryDate.' '.$setting->plan_close_time);
            $realOpen = Carbon::parse($entryDate.' '.$setting->realization_open_time);
            $realClose = Carbon::parse($entryDate.' '.$setting->realization_close_time);

            // Pola status mingguan (supaya terlihat “real”)
            $isMissingPlan = in_array($dayIndex, [1], true); // 1 hari missing plan
            $isLatePlan = in_array($dayIndex, [3, 4], true); // 2 hari late plan
            $isLateReal = in_array($dayIndex, [2], true); // 1 hari late realisasi

            $entry = DailyEntry::query()->create([
                'user_id' => $userId,
                'entry_date' => $entryDate,
                'plan_status' => $isMissingPlan ? 'missing' : ($isLatePlan ? 'late' : 'submitted'),
                'plan_submitted_at' => $isMissingPlan
                    ? null
                    : ($isLatePlan ? $planClose->copy()->addMinutes(25) : $planOpen->copy()->addMinutes(30)),
                'realization_status' => $isMissingPlan ? 'missing' : ($isLateReal ? 'late' : 'submitted'),
                'realization_submitted_at' => $isMissingPlan
                    ? null
                    : ($isLateReal ? $realClose->copy()->addMinutes(10) : $realOpen->copy()->addMinutes(60)),
            ]);

            if ($isMissingPlan) {
                continue;
            }

            // Pilih Big Rock utama
            $mainBigRock = $demo['big_rocks'][array_key_first($demo['big_rocks'])];
            $mainRoadmaps = $demo['roadmaps'][$mainBigRock->title] ?? [];
            $mainRoadmap = $mainRoadmaps ? collect($mainRoadmaps)->values()->get($dayIndex % max(count($mainRoadmaps), 1)) : null;

            $repeat = $lastFiveWorkdays->contains(fn (Carbon $d) => $d->toDateString() === $entryDate);

            $planTitle = $repeat
                ? 'Review progres Big Rock (update singkat)'
                : match ($dayIndex % 3) {
                    0 => 'Koordinasi pekerjaan prioritas hari ini',
                    1 => 'Eksekusi pekerjaan utama sesuai roadmap',
                    default => 'Follow up kendala & perbaikan proses',
                };

            $planText = $repeat
                ? 'Melakukan review cepat terhadap progres minggu ini dan menyiapkan langkah lanjutan.'
                : 'Menjalankan pekerjaan sesuai prioritas dan memastikan hasilnya tercatat dengan jelas.';

            $relationReason = $repeat
                ? 'Agar progres Big Rock terlihat konsisten setiap hari dan tidak hanya “sekali-sekali”.'
                : 'Karena aktivitas ini langsung mendorong target Big Rock dan roadmap berjalan.';

            // 1–2 item per hari (biar terlihat “multi item”)
            DB::transaction(function () use ($entry, $mainBigRock, $mainRoadmap, $planTitle, $planText, $relationReason, $dayIndex): void {
                DailyEntryItem::query()->create([
                    'daily_entry_id' => $entry->id,
                    'big_rock_id' => $mainBigRock->id,
                    'roadmap_item_id' => $mainRoadmap?->id,
                    'plan_title' => $planTitle,
                    'plan_text' => $planText,
                    'plan_relation_reason' => $relationReason,
                    'realization_status' => $dayIndex % 4 === 0 ? 'partial' : 'done',
                    'realization_text' => $dayIndex % 4 === 0
                        ? 'Sebagian selesai, ada hambatan kecil di lapangan.'
                        : 'Selesai sesuai rencana, progres tercatat.',
                    'realization_reason' => $dayIndex % 4 === 0 ? 'Ada pekerjaan mendadak yang menggeser sebagian waktu.' : null,
                ]);

                if ($dayIndex % 2 === 0) {
                    DailyEntryItem::query()->create([
                        'daily_entry_id' => $entry->id,
                        'big_rock_id' => $mainBigRock->id,
                        'roadmap_item_id' => null,
                        'plan_title' => 'Administrasi ringan terkait eksekusi',
                        'plan_text' => 'Merangkum hasil kerja agar mudah dibaca tim dan atasan.',
                        'plan_relation_reason' => 'Supaya progres Big Rock terdokumentasi rapi dan bisa dipakai untuk evaluasi.',
                        'realization_status' => 'done',
                        'realization_text' => 'Selesai dan sudah dirapikan.',
                        'realization_reason' => null,
                    ]);
                }
            });
        }
    }
}
