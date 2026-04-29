<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\DailyEntryItem;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\NotificationLog;
use App\Models\ReportSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * NOTE: Nama class dipertahankan untuk kompatibilitas (command + admin settings),
 * namun fungsinya sudah bergeser menjadi notifikasi Planning/Realisasi harian.
 */
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

        $mainWebhook = trim((string) ($setting->discord_webhook_url ?? ''));
        if ($mainWebhook === '') {
            return;
        }

        $date = $now->toDateString();
        $planDue = $this->timeForDate($date, (string) $setting->plan_close_time)->addMinutes(30);
        $realDue = $this->timeForDate($date, (string) $setting->realization_close_time)->addMinutes(30);

        if ($now->gte($planDue)) {
            $this->sendForDate(Carbon::parse($date), kind: 'plan', now: $now);
        }

        if ($now->gte($realDue)) {
            $this->sendForDate(Carbon::parse($date), kind: 'realization', now: $now);
        }
    }

    public function sendForDate(Carbon $date, string $kind = 'plan', ?Carbon $now = null, bool $force = false): void
    {
        $now = $now ?: Carbon::now();
        $setting = ReportSetting::current();

        if (! $setting->discord_enabled) {
            return;
        }

        $mainWebhook = trim((string) ($setting->discord_webhook_url ?? ''));
        if ($mainWebhook === '') {
            return;
        }

        if (! in_array($kind, ['plan', 'realization'], true)) {
            throw new \InvalidArgumentException('Invalid kind: '.$kind);
        }

        $day = $date->toDateString();

        if (! $this->isWorkday($date)) {
            NotificationLog::query()->updateOrCreate(
                [
                    'channel' => 'discord',
                    'type' => "daily_{$kind}_main",
                    'context_date' => $day,
                ],
                [
                    'status' => 'skipped',
                    'summary' => 'Skip: weekend/holiday.',
                    'payload' => ['kind' => $kind],
                    'error_message' => null,
                    'sent_at' => null,
                    'failed_at' => null,
                ],
            );

            return;
        }

        $closeTime = $kind === 'plan' ? (string) $setting->plan_close_time : (string) $setting->realization_close_time;
        $closeAt = $this->timeForDate($day, $closeTime);

        if (! $force && $now->lt($closeAt)) {
            return;
        }

        $eligibleUsers = $this->eligibleUsersForDate($date);
        if ($eligibleUsers->isEmpty()) {
            NotificationLog::query()->updateOrCreate(
                [
                    'channel' => 'discord',
                    'type' => "daily_{$kind}_main",
                    'context_date' => $day,
                ],
                [
                    'status' => 'skipped',
                    'summary' => 'Skip: no eligible users.',
                    'payload' => ['kind' => $kind],
                    'error_message' => null,
                    'sent_at' => null,
                    'failed_at' => null,
                ],
            );

            return;
        }

        $mainType = "daily_{$kind}_main";
        if (! $force && $this->shouldSkipDueToLog($mainType, $day, $now)) {
            return;
        }

        $statusRows = $this->computeStatuses($eligibleUsers, $date, $kind, $closeAt);
        $okNames = $statusRows->where('status', 'submitted')->pluck('name')->values()->all();
        $lateNames = $statusRows->where('status', 'late')->pluck('name')->values()->all();
        $missingNames = $statusRows->where('status', 'missing')->pluck('name')->values()->all();

        // Per-user messages (skip if webhook missing).
        foreach ($statusRows as $row) {
            $userWebhook = trim((string) ($row['discord_webhook_url'] ?? ''));
            if ($userWebhook === '') {
                continue;
            }

            $userId = (int) $row['id'];
            $userType = "daily_{$kind}_user_{$userId}";

            $existing = NotificationLog::query()
                ->where('channel', 'discord')
                ->where('type', $userType)
                ->whereDate('context_date', $day)
                ->first();

            if (! $force && $existing && in_array($existing->status, ['sent', 'skipped'], true)) {
                continue;
            }

            if (! $force && $existing && $existing->status === 'failed') {
                $lastAttempt = $existing->failed_at ?? $existing->updated_at;
                if ($lastAttempt) {
                    $minutes = Carbon::parse($lastAttempt)->diffInMinutes($now);
                    if ($minutes < self::RETRY_COOLDOWN_MINUTES) {
                        continue;
                    }
                }
            }

            if (! $force && $existing && $existing->status === 'pending') {
                continue;
            }

            $payload = $this->buildPerUserPayload($row, $date, $kind);

            $log = NotificationLog::query()->updateOrCreate(
                [
                    'channel' => 'discord',
                    'type' => $userType,
                    'context_date' => $day,
                ],
                [
                    'status' => 'pending',
                    'summary' => "Daily {$kind} for user #{$userId}",
                    'payload' => $payload['context'],
                    'error_message' => null,
                    'sent_at' => null,
                    'failed_at' => null,
                ],
            );

            try {
                foreach ($payload['contents'] as $chunk) {
                    app(DiscordWebhookClient::class)->send($userWebhook, ['content' => $chunk]);
                }

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
            }
        }

        // Main channel message.
        $mainContent = $this->buildMainContent($date, $kind, $okNames, $lateNames, $missingNames);

        $mainLog = NotificationLog::query()->updateOrCreate(
            [
                'channel' => 'discord',
                'type' => $mainType,
                'context_date' => $day,
            ],
            [
                'status' => 'pending',
                'summary' => "Daily {$kind} main summary",
                'payload' => [
                    'kind' => $kind,
                    'counts' => [
                        'ok' => count($okNames),
                        'late' => count($lateNames),
                        'missing' => count($missingNames),
                    ],
                    'late_names' => $lateNames,
                    'missing_names' => $missingNames,
                ],
                'error_message' => null,
                'sent_at' => null,
                'failed_at' => null,
            ],
        );

        try {
            app(DiscordWebhookClient::class)->send($mainWebhook, ['content' => $mainContent]);

            $mainLog->status = 'sent';
            $mainLog->sent_at = now();
            $mainLog->failed_at = null;
            $mainLog->error_message = null;
            $mainLog->save();
        } catch (\Throwable $e) {
            $mainLog->status = 'failed';
            $mainLog->error_message = $e->getMessage();
            $mainLog->failed_at = now();
            $mainLog->save();

            throw $e;
        }
    }

    private function timeForDate(string $dateYmd, string $timeHi): Carbon
    {
        return Carbon::parse($dateYmd.' '.Carbon::parse($timeHi)->format('H:i'));
    }

    private function isWorkday(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        return ! Holiday::query()
            ->whereDate('holiday_date', $date->toDateString())
            ->where('is_holiday', true)
            ->where('is_joint_holiday', false)
            ->exists();
    }

    /**
     * @return Collection<int,User>
     */
    private function eligibleUsersForDate(Carbon $date): Collection
    {
        $users = User::query()
            ->whereIn('role', ['hod', 'manager'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'division_id', 'role', 'discord_webhook_url']);

        if ($users->isEmpty()) {
            return $users;
        }

        $day = $date->toDateString();
        $leaveUserIds = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        if (empty($leaveUserIds)) {
            return $users;
        }

        $skipSet = array_fill_keys($leaveUserIds, true);
        return $users->reject(fn (User $u) => isset($skipSet[$u->id]))->values();
    }

    private function computeStatuses(Collection $users, Carbon $date, string $kind, Carbon $closeAt): Collection
    {
        $ids = $users->pluck('id')->all();
        $day = $date->toDateString();

        $entries = DailyEntry::query()
            ->whereIn('user_id', $ids)
            ->whereDate('entry_date', $day)
            ->get(['id', 'user_id', 'plan_submitted_at', 'realization_submitted_at'])
            ->keyBy('user_id');

        return $users->map(function (User $u) use ($entries, $kind, $closeAt) {
            /** @var DailyEntry|null $entry */
            $entry = $entries->get($u->id);

            $submittedAt = null;
            if ($entry) {
                $submittedAt = $kind === 'plan' ? $entry->plan_submitted_at : $entry->realization_submitted_at;
            }

            $status = 'missing';
            if ($submittedAt) {
                $status = Carbon::parse($submittedAt)->gt($closeAt) ? 'late' : 'submitted';
            }

            return [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'role' => (string) $u->role,
                'division_id' => $u->division_id,
                'discord_webhook_url' => $u->discord_webhook_url,
                'status' => $status,
                'submitted_at' => $submittedAt ? Carbon::parse($submittedAt)->toDateTimeString() : null,
            ];
        });
    }

    private function shouldSkipDueToLog(string $type, string $day, Carbon $now): bool
    {
        $log = NotificationLog::query()
            ->where('channel', 'discord')
            ->where('type', $type)
            ->whereDate('context_date', $day)
            ->first();

        if ($log && in_array($log->status, ['sent', 'skipped'], true)) {
            return true;
        }

        if ($log && $log->status === 'failed') {
            $lastAttempt = $log->failed_at ?? $log->updated_at;
            if ($lastAttempt) {
                $minutes = Carbon::parse($lastAttempt)->diffInMinutes($now);
                if ($minutes < self::RETRY_COOLDOWN_MINUTES) {
                    return true;
                }
            }
        }

        if ($log && $log->status === 'pending') {
            return true;
        }

        return false;
    }

    private function buildPerUserPayload(array $row, Carbon $date, string $kind): array
    {
        $label = $kind === 'plan' ? 'Planning' : 'Realisasi';
        $dateLabel = $date->translatedFormat('l, j F Y');
        $status = (string) ($row['status'] ?? 'missing');

        if ($status === 'missing') {
            $content = $label.' - '.$row['name'].' - '.$dateLabel."\n"
                .'- Status: Missing';

            return [
                'contents' => [$content],
                'context' => [
                    'kind' => $kind,
                    'user_id' => (int) $row['id'],
                    'status' => 'missing',
                    'date' => $date->toDateString(),
                ],
            ];
        }

        $entry = DailyEntry::query()
            ->where('user_id', (int) $row['id'])
            ->whereDate('entry_date', $date->toDateString())
            ->first(['id']);

        $items = collect();
        if ($entry) {
            $items = DailyEntryItem::query()
                ->with(['bigRock:id,title', 'roadmapItem:id,title', 'attachments:id,daily_entry_item_id,original_name,path'])
                ->where('daily_entry_id', $entry->id)
                ->orderBy('id')
                ->get([
                    'id',
                    'daily_entry_id',
                    'plan_title',
                    'plan_text',
                    'plan_relation_reason',
                    'plan_duration_minutes',
                    'realization_status',
                    'realization_text',
                    'realization_reason',
                    'realization_duration_minutes',
                ]);
        }

        $lines = [];
        $lines[] = $label.' - '.$row['name'].' - '.$dateLabel;
        $lines[] = '- Status Laporan: '.($status === 'late' ? 'Telat' : 'Submitted');

        $blocks = [];
        foreach ($items as $idx => $it) {
            $n = $idx + 1;
            $bigRock = $it->bigRock?->title ?? '-';
            $roadmap = $it->roadmapItem?->title ?? '-';

            if ($kind === 'plan') {
                $duration = $this->formatDuration((int) ($it->plan_duration_minutes ?? 0));
                $desc = trim((string) ($it->plan_text ?? ''));
                $reason = trim((string) ($it->plan_relation_reason ?? ''));
                $blocks[] = implode("\n", [
                    "Item {$n}",
                    '- Judul Planning: '.(trim((string) $it->plan_title) !== '' ? (string) $it->plan_title : '-'),
                    '- Big Rock: '.$bigRock,
                    '- Roadmap: '.$roadmap,
                    '- Durasi: '.$duration,
                    '- Deskripsi: '.($desc !== '' ? $desc : 'Tidak ada deskripsi.'),
                    '- Kenapa rencana ini terkait Big Rock tersebut?: '.($reason !== '' ? $reason : '-'),
                ]);
            } else {
                $durationMinutes = (int) ($it->realization_duration_minutes ?? $it->plan_duration_minutes ?? 0);
                $duration = $this->formatDuration($durationMinutes);
                $desc = trim((string) ($it->realization_text ?? ''));
                $reason = trim((string) ($it->realization_reason ?? ''));
                $jobStatus = $this->formatRealizationJobStatus((string) ($it->realization_status ?? ''));
                $titleReal = $desc !== '' ? Str::limit(preg_split("/\\r\\n|\\r|\\n/", $desc)[0] ?? $desc, 80) : '-';

                $attachmentLabel = $this->formatAttachmentLines($it->attachments);

                $blocks[] = implode("\n", [
                    "Item {$n}",
                    '- Judul Planning: '.(trim((string) $it->plan_title) !== '' ? (string) $it->plan_title : '-'),
                    '- Judul Realisasi: '.$titleReal,
                    '- Status: '.$jobStatus,
                    '- Durasi: '.$duration,
                    '- Deskripsi: '.($desc !== '' ? $desc : 'Tidak ada deskripsi.'),
                    '- Alasan jika  belum selesai / blocked: '.($reason !== '' ? $reason : '-'),
                    '- Big Rock: '.$bigRock,
                    '- Roadmap: '.$roadmap,
                    '- Lampiran: '.$attachmentLabel,
                ]);
            }
        }

        if (! empty($blocks)) {
            $lines[] = '';
            $lines[] = implode("\n\n", $blocks);
        }

        $summary = $this->aiSummaryPerUser($kind, (string) $row['name'], $date, $items);
        if ($summary !== '') {
            $lines[] = '';
            $lines[] = 'Summary: '.$summary;
        }

        $content = implode("\n", $lines);

        return [
            'contents' => $this->splitDiscordMessage($content),
            'context' => [
                'kind' => $kind,
                'user_id' => (int) $row['id'],
                'status' => $status,
                'date' => $date->toDateString(),
                'item_ids' => $items->pluck('id')->all(),
            ],
        ];
    }

    private function buildMainContent(Carbon $date, string $kind, array $okNames, array $lateNames, array $missingNames): string
    {
        $label = $kind === 'plan' ? 'Planning' : 'Realisasi';
        $dateLabel = $date->translatedFormat('l, j F Y');

        $lines = [];
        $lines[] = $label.' - '.$dateLabel;
        $lines[] = '- Jumlah ok: '.count($okNames);
        $lines[] = '- Jumlah Telat: '.count($lateNames).(count($lateNames) ? ' -- '.implode(', ', $lateNames) : '');
        $lines[] = '- Jumlah Missing: '.count($missingNames).(count($missingNames) ? ' -- '.implode(', ', $missingNames) : '');

        $summary = $this->aiSummaryMain($kind, $date, $okNames, $lateNames, $missingNames);
        if ($summary !== '') {
            $lines[] = 'Summary: '.$summary;
        }

        return Str::limit(implode("\n", $lines), 1900, "\n...(dipotong)");
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return '-';
        }

        if ($minutes % 60 === 0) {
            return ((int) ($minutes / 60)).' Jam';
        }

        if ($minutes > 60) {
            $hours = intdiv($minutes, 60);
            $mins = $minutes % 60;
            return "{$hours} Jam {$mins} Menit";
        }

        return $minutes.' Menit';
    }

    private function formatRealizationJobStatus(string $raw): string
    {
        return match ($raw) {
            'done' => 'Selesai',
            'partial' => 'Sebagian',
            'not_done' => 'Tidak Dikerjakan',
            'blocked' => 'Blocked',
            default => trim($raw) !== '' ? $raw : '-',
        };
    }

    private function formatAttachmentLines($attachments): string
    {
        if (! $attachments || count($attachments) === 0) {
            return '-';
        }

        $parts = [];
        foreach ($attachments as $a) {
            $name = trim((string) ($a->original_name ?? 'file'));
            if ($name === '') $name = 'file';
            $url = Storage::url((string) $a->path);
            $parts[] = $name.': '.$url;
        }

        return implode(' | ', $parts);
    }

    private function splitDiscordMessage(string $content): array
    {
        $content = trim((string) $content);
        if ($content === '') {
            return [];
        }

        $max = 1900;
        if (mb_strlen($content) <= $max) {
            return [Str::limit($content, $max, '')];
        }

        $chunks = [];
        $lines = preg_split("/\\r\\n|\\r|\\n/", $content) ?: [$content];

        $current = '';
        foreach ($lines as $line) {
            $candidate = $current === '' ? $line : $current."\n".$line;
            if (mb_strlen($candidate) > $max) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = $line;
                    continue;
                }

                $chunks[] = Str::limit($line, $max, '');
                $current = '';
                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function aiSummaryPerUser(string $kind, string $userName, Carbon $date, $items): string
    {
        try {
            $system = "Anda adalah asisten ringkasan harian yang netral dan singkat.\n"
                ."Tulis 1-2 kalimat Bahasa Indonesia yang menilai apakah laporan {$kind} terlihat selaras dengan Big Rock/Roadmap dan tidak sekadar formalitas.\n"
                ."Jangan menghakimi; gunakan gaya profesional dan faktual.";

            $context = [
                'kind' => $kind,
                'date' => $date->toDateString(),
                'user' => $userName,
                'items' => $items->map(function ($it) {
                    return [
                        'plan_title' => $it->plan_title,
                        'big_rock' => $it->bigRock?->title,
                        'roadmap' => $it->roadmapItem?->title,
                        'plan_duration_minutes' => $it->plan_duration_minutes,
                        'plan_text' => $it->plan_text,
                        'plan_relation_reason' => $it->plan_relation_reason,
                        'realization_status' => $it->realization_status,
                        'realization_text' => $it->realization_text,
                        'realization_reason' => $it->realization_reason,
                        'realization_duration_minutes' => $it->realization_duration_minutes,
                    ];
                })->all(),
            ];

            $user = $kind === 'plan'
                ? 'Ringkas selaras/tidaknya rencana dengan Big Rock/Roadmap.'
                : 'Ringkas selaras/tidaknya realisasi dengan rencana dan Big Rock/Roadmap.';

            $text = app(OpenAIResponsesClient::class)->respondJson($system, $user, $context);
            return trim(Str::of($text)->replace(["\r", "\n"], ' ')->squish()->toString());
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function aiSummaryMain(string $kind, Carbon $date, array $okNames, array $lateNames, array $missingNames): string
    {
        try {
            $system = "Anda adalah asisten ringkasan organisasi yang netral dan singkat.\n"
                ."Tulis 1-2 kalimat Bahasa Indonesia untuk rekap {$kind} hari ini.\n"
                ."Fokus pada kualitas pengisian secara umum (selaras vs formalitas), tanpa menghakimi.";

            $context = [
                'kind' => $kind,
                'date' => $date->toDateString(),
                'counts' => [
                    'ok' => count($okNames),
                    'late' => count($lateNames),
                    'missing' => count($missingNames),
                ],
                'late_names' => $lateNames,
                'missing_names' => $missingNames,
            ];

            $text = app(OpenAIResponsesClient::class)->respondJson($system, 'Buat summary singkat berdasarkan counts.', $context);
            return trim(Str::of($text)->replace(["\r", "\n"], ' ')->squish()->toString());
        } catch (\Throwable $e) {
            return '';
        }
    }
}
