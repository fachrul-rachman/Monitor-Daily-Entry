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

Artisan::command('dayta:sync-holidays {year?}', function () {
    $yearArg = $this->argument('year');
    $year = $yearArg ? (int) $yearArg : (int) Carbon::now()->format('Y');

    try {
        $count = app(\App\Services\HolidaysSyncService::class)->syncIndonesiaPublicHolidays($year);
        $this->info("OK: holidays synced for {$year} ({$count} dates saved).");
    } catch (\Throwable $e) {
        $this->error('FAILED: '.$e->getMessage());
    }
})->purpose('Sync Indonesia public holidays (joint holidays excluded)');

Artisan::command('dayta:migrate-a-to-b
    {--attachments-root= : Path folder lokal yang berisi daily_attachments/... (hasil download dari Apps A)}
    {--report= : Path output laporan markdown}
    {--db-a-host= : Host Postgres untuk db A}
    {--db-a-port= : Port Postgres untuk db A}
    {--db-a-database= : Nama database db A (contoh: daytaDb)}
    {--db-a-username= : Username Postgres untuk db A}
    {--db-a-password= : Password Postgres untuk db A}
    {--dry-run : Cek koneksi + cek file attachment tanpa insert ke db B}
', function () {
    $attachmentsRoot = (string) ($this->option('attachments-root') ?? '');
    if (trim($attachmentsRoot) === '') {
        $this->error('attachments-root wajib diisi.');
        return;
    }

    $dbAHost = (string) ($this->option('db-a-host') ?? env('DB_A_HOST', ''));
    $dbAPort = (int) ($this->option('db-a-port') ?? env('DB_A_PORT', 0));
    $dbADatabase = (string) ($this->option('db-a-database') ?? env('DB_A_DATABASE', ''));
    $dbAUsername = (string) ($this->option('db-a-username') ?? env('DB_A_USERNAME', ''));
    $dbAPassword = (string) ($this->option('db-a-password') ?? env('DB_A_PASSWORD', ''));

    if (trim($dbAHost) === '' || $dbAPort <= 0 || trim($dbADatabase) === '' || trim($dbAUsername) === '' || trim($dbAPassword) === '') {
        $this->error('Koneksi db A belum lengkap (host/port/database/username/password).');
        return;
    }

    $reportPath = (string) ($this->option('report') ?? '');
    if (trim($reportPath) === '') {
        $reportPath = storage_path('app/private/migration-reports/dayta_migration_'.now()->format('Ymd_His').'.md');
    }

    $dryRun = (bool) $this->option('dry-run');

    try {
        $result = app(\App\Services\DaytaMigrationService::class)->migrate([
            'attachments_root' => $attachmentsRoot,
            'db_a' => [
                'host' => $dbAHost,
                'port' => $dbAPort,
                'database' => $dbADatabase,
                'username' => $dbAUsername,
                'password' => $dbAPassword,
            ],
            'report_path' => $reportPath,
            'preserve_item_ids' => true,
            'default_relation_reason' => 'Migrasi data lama',
            'map_real_only_plan_title_prefix' => '(Migrasi) ',
            'dry_run' => $dryRun,
        ]);

        $this->info('OK: migration finished.');
        $this->info('Report: '.$result['report_path']);
    } catch (\Throwable $e) {
        $this->error('FAILED: '.$e->getMessage());
        $this->error('Report (partial): '.$reportPath);
    }
})->purpose('Migrate Apps A (db A) → Apps B (db B) + copy attachments (local)');
