<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dayta:compute-metrics {--from=} {--to=}', function () {
    $fromOpt = $this->option('from');
    $toOpt = $this->option('to');

    $to = $toOpt ? Carbon::parse($toOpt) : Carbon::yesterday();
    $from = $fromOpt ? Carbon::parse($fromOpt) : $to->copy()->subDays(7);

    app(\App\Services\MetricsService::class)->compute($from, $to);

    $this->info('OK: metrics computed.');
})->purpose('Compute findings and health scores for a date range');

Artisan::command('dayta:send-discord-daily-summary {--date=}', function () {
    $dateOpt = $this->option('date');

    try {
        if ($dateOpt) {
            $date = Carbon::parse($dateOpt);
            app(\App\Services\DiscordDailySummaryService::class)->sendForDate($date);
            $this->info('OK: discord daily summary sent for '.$date->toDateString().'.');

            return;
        }

        app(\App\Services\DiscordDailySummaryService::class)->sendIfDue(Carbon::now());
        $this->info('OK: discord daily summary checked.');
    } catch (\Throwable $e) {
        $this->error('FAILED: '.$e->getMessage());
    }
})->purpose('Send Discord daily findings summary (medium/high) for Director');
