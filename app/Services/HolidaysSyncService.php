<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HolidaysSyncService
{
    public function __construct(private readonly HolidaysApiClient $client)
    {
    }

    public function syncIndonesiaPublicHolidays(int $year): int
    {
        $rows = $this->client->fetchIndonesia($year);

        $saved = 0;

        DB::transaction(function () use ($rows, $year, &$saved): void {
            foreach ($rows as $r) {
                $date = (string) ($r['date'] ?? '');
                if (trim($date) === '') {
                    continue;
                }

                try {
                    $holidayDate = Carbon::parse($date)->toDateString();
                } catch (\Throwable) {
                    continue;
                }

                $isHoliday = (bool) ($r['is_holiday'] ?? false);
                $isJoint = (bool) ($r['is_joint_holiday'] ?? false);
                $isObservance = (bool) ($r['is_observance'] ?? false);

                // Rule kantor: joint holiday tetap dihitung sebagai hari kerja → jangan disimpan sebagai "off".
                if (! $isHoliday || $isJoint) {
                    // Kalau sebelumnya tersimpan, hapus supaya rule tetap konsisten.
                    Holiday::query()->whereDate('holiday_date', $holidayDate)->delete();
                    continue;
                }

                Holiday::query()->updateOrCreate(
                    ['holiday_date' => $holidayDate],
                    [
                        'source_id' => isset($r['id']) ? (int) $r['id'] : null,
                        'name' => (string) ($r['name'] ?? ''),
                        'type' => (string) ($r['type'] ?? ''),
                        'year' => (int) ($r['year'] ?? $year),
                        'is_holiday' => $isHoliday,
                        'is_joint_holiday' => $isJoint,
                        'is_observance' => $isObservance,
                    ],
                );

                $saved++;
            }
        });

        return $saved;
    }
}

