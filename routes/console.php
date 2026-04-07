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
