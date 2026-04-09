<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Metrics (findings + health score) untuk chart Director/HoD.
        // MVP: hitung ulang 14 hari terakhir secara idempotent (aman dijalankan berulang).
        $schedule->command('dayta:compute-metrics --from='.now()->subDays(14)->toDateString().' --to='.now()->toDateString())
            ->everyFifteenMinutes();

        // MVP: cek tiap menit, service akan kirim hanya jika sudah waktunya dan belum pernah terkirim hari itu.
        $schedule->command('dayta:send-discord-daily-summary')->everyMinute();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
