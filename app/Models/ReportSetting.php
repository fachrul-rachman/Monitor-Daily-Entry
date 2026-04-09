<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ReportSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_open_time',
        'plan_close_time',
        'realization_open_time',
        'realization_close_time',
        'effective_from',
        'is_active',
        'discord_enabled',
        'discord_summary_time',
        'discord_webhook_url',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'is_active' => 'boolean',
        'discord_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        /** @var self|null $setting */
        $setting = static::where('is_active', true)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($setting) {
            return $setting;
        }

        // Jika belum ada data sama sekali, buat default agar sistem tidak error.
        /** @var self $created */
        $created = static::query()->create([
            'plan_open_time' => '07:00',
            'plan_close_time' => '10:00',
            'realization_open_time' => '15:00',
            'realization_close_time' => '23:00',
            'effective_from' => Carbon::today()->toDateString(),
            'is_active' => true,
            'discord_enabled' => false,
            'discord_summary_time' => '20:00',
            'discord_webhook_url' => null,
        ]);

        return $created;
    }
}
