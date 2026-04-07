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
    ];

    protected $casts = [
        'effective_from' => 'date',
        'is_active' => 'boolean',
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

        // Fallback default (should rarely be used once Admin sets it).
        return new self([
            'plan_open_time' => '07:00',
            'plan_close_time' => '10:00',
            'realization_open_time' => '15:00',
            'realization_close_time' => '23:00',
            'effective_from' => Carbon::today(),
            'is_active' => true,
        ]);
    }
}

