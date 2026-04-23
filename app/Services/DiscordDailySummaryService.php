<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\NotificationLog;
use App\Models\ReportSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DiscordDailySummaryService
{
    private const RETRY_COOLDOWN_MINUTES = 10;

    public function sendIfDue(?Carbon $now = null): void
    {
        $now = $now ?: Carbon::now();
        $setting = ReportSetting::current();

        if (! $setting->discord_enabled) {
            return;
        }

        $webhook = (string) ($setting->discord_webhook_url ?? '');
        if (trim($webhook) === '') {
            return;
        }

        $summaryTime = Carbon::parse($now->toDateString().' '.($setting->discord_summary_time ?? '20:00'));

        // Belum waktunya.
        if ($now->lt($summaryTime)) {
            return;
        }

        $date = $now->toDateString();

        $log = NotificationLog::query()
            ->where('channel', 'discord')
            ->where('type', 'daily_summary')
            ->whereDate('context_date', $date)
            ->first();

        // Dedupe: kalau sudah sukses / sudah diputuskan skip, stop.
        if ($log && in_array($log->status, ['sent', 'skipped'], true)) {
            return;
        }

        // Kalau sebelumnya gagal, retry tapi jangan terlalu sering.
        if ($log && $log->status === 'failed') {
            $lastAttempt = $log->failed_at ?? $log->updated_at;
            if ($lastAttempt) {
                $minutes = Carbon::parse($lastAttempt)->diffInMinutes($now);
                if ($minutes < self::RETRY_COOLDOWN_MINUTES) {
                    return;
                }
            }
        }

        // Jika sedang pending (mis. overlap), biarkan run lain yang menyelesaikan.
        if ($log && $log->status === 'pending') {
            return;
        }

        $this->sendForDate(Carbon::parse($date));
    }

    public function sendForDate(Carbon $date): void
    {
        $setting = ReportSetting::current();

        if (! $setting->discord_enabled) {
            return;
        }

        $webhook = (string) ($setting->discord_webhook_url ?? '');
        if (trim($webhook) === '') {
            return;
        }

        $day = $date->toDateString();

        $findings = Finding::query()
            ->with(['user:id,name', 'division:id,name'])
            ->whereDate('finding_date', $day)
            ->whereIn('severity', ['medium', 'high'])
            ->orderByRaw("case when severity='high' then 0 else 1 end")
            ->orderByDesc('id')
            ->get(['id', 'finding_date', 'severity', 'title', 'user_id', 'division_id', 'type']);

        // Kalau tidak ada temuan medium/high: tidak kirim.
        if ($findings->isEmpty()) {
            NotificationLog::query()->updateOrCreate(
                [
                    'channel' => 'discord',
                    'type' => 'daily_summary',
                    'context_date' => $day,
                ],
                [
                    'status' => 'skipped',
                    'summary' => 'Tidak ada temuan medium/high hari ini.',
                    'payload' => [
                        'counts' => ['high' => 0, 'medium' => 0],
                    ],
                    'error_message' => null,
                    'sent_at' => null,
                    'failed_at' => null,
                ],
            );

            return;
        }

        $highCount = $findings->where('severity', 'high')->count();
        $mediumCount = $findings->where('severity', 'medium')->count();
        $total = $findings->count();

        $title = 'Dayta — Daily Findings Summary';
        $header = '**'.$title.'**';
        $sub = $date->translatedFormat('j M Y').' • High: '.$highCount.' • Medium: '.$mediumCount;

        $lines = [];
        $maxItems = 12;
        foreach ($findings->take($maxItems) as $f) {
            $sev = $f->severity === 'high' ? 'HIGH' : 'MED';
            $division = $f->division?->name ?? '-';
            $user = $f->user?->name ?? '-';
            $t = trim((string) $f->title);
            if ($t === '') $t = '(tanpa judul)';
            $lines[] = "- **{$sev}** • {$division} • {$user} — {$t}";
        }

        $remaining = max($total - $maxItems, 0);
        if ($remaining > 0) {
            $lines[] = "- ...dan {$remaining} temuan lainnya (buka web untuk detail).";
        }

        $content = $header."\n".$sub."\n\n".implode("\n", $lines);
        $content = Str::limit($content, 1900, "\n...(dipotong, buka web untuk detail)");

        $payload = [
            'content' => $content,
        ];

        $log = NotificationLog::query()->updateOrCreate(
            [
                'channel' => 'discord',
                'type' => 'daily_summary',
                'context_date' => $day,
            ],
            [
                'status' => 'pending',
                'summary' => "Daily findings summary ({$highCount} high, {$mediumCount} medium)",
                'payload' => [
                    'counts' => ['high' => $highCount, 'medium' => $mediumCount],
                    'finding_ids' => $findings->take($maxItems)->pluck('id')->all(),
                ],
                'error_message' => null,
                'sent_at' => null,
                'failed_at' => null,
            ],
        );

        try {
            app(DiscordWebhookClient::class)->send($webhook, $payload);

            $log->status = 'sent';
            $log->sent_at = now();
            $log->failed_at = null;
            $log->error_message = null;
            $log->save();
        } catch (\Throwable $e) {
            $log->status = 'failed';
            $log->error_message = $e->getMessage();
            $log->failed_at = now();
            $log->save();

            throw $e;
        }
    }
}
