<?php

namespace App\Livewire\Hod;

use App\Models\DailyEntry;
use App\Models\Finding;
use App\Models\Holiday;
use App\Models\HealthScore;
use App\Models\HodAssignment;
use App\Models\User;
use App\Services\OpenAIResponsesClient;
use Illuminate\Support\Carbon;
use Livewire\Component;

class AiChatPage extends Component
{
    public string $messageInput = '';

    /** @var array<int, array{role: string, content: string, points?: array<int, string>}> */
    public array $messages = [];

    /** @var array<int, string> */
    public array $suggestedPrompts = [
        'Siapa yang paling sering missing minggu ini di divisi saya?',
        'Apa 3 temuan terbesar 7 hari terakhir di divisi saya?',
        'Apa pola keterlambatan yang paling sering terjadi?',
        'Ringkas kondisi divisi saya hari ini (singkat).',
        'Apa yang harus saya follow up besok pagi?',
    ];

    public function mount(): void
    {
        $this->messages = [
            [
                'role' => 'ai',
                'content' => 'Silakan tanya tentang kondisi divisi Anda (hari ini atau 7 hari terakhir). Saya jawab singkat dan langsung ke poin.',
                'points' => [
                    'Contoh: "Siapa paling sering missing minggu ini?"',
                    'Contoh: "Temuan medium/high apa yang perlu saya follow up?"',
                ],
            ],
        ];
    }

    public function sendSuggested(string $prompt): void
    {
        $this->messageInput = $prompt;
        $this->sendMessage();
    }

    public function sendMessage(): void
    {
        $text = trim($this->messageInput);
        if ($text === '') {
            return;
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $text,
        ];

        $this->messageInput = '';

        $client = app(OpenAIResponsesClient::class);

        $system = $this->buildSystemMessage();
        $context = $this->buildContextSnapshot();

        try {
            $raw = $client->respondJson(
                system: $system,
                user: $text,
                context: $context,
            );

            $payload = $this->decodeAiPayload($raw);
            if ($payload) {
                $this->messages[] = [
                    'role' => 'ai',
                    'content' => $this->formatAiPayload($payload),
                ];

                return;
            }
        } catch (\Throwable) {
            // fallthrough
        }

        $this->messages[] = [
            'role' => 'ai',
            'content' => implode("\n", [
                'AI belum bisa memproses pertanyaan ini.',
                '',
                'Jawaban inti:',
                '- Silakan coba lagi beberapa saat.',
                '- Jika sering terjadi, minta admin memastikan konfigurasi AI sudah aktif.',
                '',
                'Catatan data: Berdasarkan data sistem yang tersedia saat ini.',
            ]),
        ];
    }

    protected function buildSystemMessage(): string
    {
        return <<<SYS
Anda adalah "Dayta AI", asisten analisis untuk Head of Division (HoD).

Aturan:
- Anda HANYA menganalisis scope divisi HoD yang ada di "Context snapshot (JSON)".
- Menjawab dengan gaya bisnis (ringkas, coaching/follow-up ready).
- Bahasa Indonesia saja.
- Fokus pada data yang ada. Jangan mengarang detail yang tidak ada.
- Jika data belum cukup untuk menjawab tepat, katakan dengan jelas di "core_answer" dan sarankan 1 langkah tindak lanjut.

Format keluaran WAJIB JSON valid saja (tanpa markdown, tanpa teks tambahan), dengan struktur:
{
  "title": "Judul singkat sesuai pertanyaan",
  "core_answer": "Jawaban inti 1–2 kalimat, langsung menjawab",
  "reasons": ["Alasan singkat berbasis data", "..."],
  "follow_up": ["Nama Manager — isu — tindakan yang diminta", "..."],
  "next_actions": ["Aksi yang disarankan", "..."],
  "data_note": "1 baris catatan data (rentang tanggal + sumber: data sistem)"
}

Aturan penting:
- Jangan tampilkan kode internal / label seperti 'missing_report_...'.
- Ikuti permintaan user (mis. jika user minta top 1, berikan top 1 saja).
- Total bullet gabungan (reasons+follow_up+next_actions) maksimal 8 item.
SYS;
    }

    /**
     * @return array<int, string>
     */
    protected function buildWorkdays(string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->startOfDay();

        $holidayDates = Holiday::query()
            ->whereBetween('holiday_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('is_holiday', true)
            ->where('is_joint_holiday', false)
            ->pluck('holiday_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
        $holidaySet = array_fill_keys($holidayDates, true);

        $workdays = [];
        $cursor = $fromDate->copy();
        while ($cursor->lte($toDate)) {
            if (! $cursor->isWeekend()) {
                $key = $cursor->toDateString();
                if (! isset($holidaySet[$key])) {
                    $workdays[] = $key;
                }
            }
            $cursor->addDay();
        }

        return $workdays;
    }

    protected function extractJsonObject(string $text): ?string
    {
        $t = trim($text);

        $t = preg_replace('/^```(?:json)?\\s*/i', '', $t) ?? $t;
        $t = preg_replace('/\\s*```\\s*$/', '', $t) ?? $t;

        $start = strpos($t, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;

        for ($i = $start; $i < strlen($t); $i++) {
            $ch = $t[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                    continue;
                }
                if ($ch === '\\') {
                    $escape = true;
                    continue;
                }
                if ($ch === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($ch === '"') {
                $inString = true;
                continue;
            }

            if ($ch === '{') $depth++;
            if ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($t, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeAiPayload(string $raw): ?array
    {
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $rawJson = $this->extractJsonObject($raw);
            if ($rawJson) {
                $decoded = json_decode($rawJson, true);
            }
        }

        if (! is_array($decoded)) {
            return null;
        }

        $title = trim((string) ($decoded['title'] ?? ''));
        $core = trim((string) ($decoded['core_answer'] ?? ''));
        $dataNote = trim((string) ($decoded['data_note'] ?? ''));

        if ($title === '' || $core === '' || $dataNote === '') {
            return null;
        }

        $decoded['reasons'] = is_array($decoded['reasons'] ?? null) ? array_values(array_map('strval', $decoded['reasons'])) : [];
        $decoded['follow_up'] = is_array($decoded['follow_up'] ?? null) ? array_values(array_map('strval', $decoded['follow_up'])) : [];
        $decoded['next_actions'] = is_array($decoded['next_actions'] ?? null) ? array_values(array_map('strval', $decoded['next_actions'])) : [];

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function formatAiPayload(array $payload): string
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $core = trim((string) ($payload['core_answer'] ?? ''));
        $reasons = array_values(array_filter(array_map('trim', (array) ($payload['reasons'] ?? []))));
        $followUp = array_values(array_filter(array_map('trim', (array) ($payload['follow_up'] ?? []))));
        $actions = array_values(array_filter(array_map('trim', (array) ($payload['next_actions'] ?? []))));
        $dataNote = trim((string) ($payload['data_note'] ?? ''));

        $lines = [];
        if ($title !== '') {
            $lines[] = $title;
        }

        if ($core !== '') {
            $lines[] = '';
            $lines[] = $core;
        }

        if (! empty($reasons)) {
            $lines[] = '';
            $lines[] = 'Alasan singkat:';
            foreach ($reasons as $r) {
                $lines[] = '- '.$r;
            }
        }

        if (! empty($followUp)) {
            $lines[] = '';
            $lines[] = 'Manager yang perlu follow-up:';
            foreach ($followUp as $f) {
                $lines[] = '- '.$f;
            }
        }

        if (! empty($actions)) {
            $lines[] = '';
            $lines[] = 'Tindak lanjut:';
            foreach ($actions as $a) {
                $lines[] = '- '.$a;
            }
        }

        if ($dataNote !== '') {
            $lines[] = '';
            $lines[] = 'Catatan data: '.$dataNote;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int>
     */
    protected function assignedDivisionIds(): array
    {
        $hod = auth()->user();
        if (! $hod) {
            return [];
        }

        $ids = HodAssignment::query()
            ->where('hod_id', $hod->id)
            ->pluck('division_id')
            ->filter()
            ->values()
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($ids) && $hod->division_id) {
            $ids = [(int) $hod->division_id];
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildContextSnapshot(): array
    {
        $hod = auth()->user();
        $divisionIds = $this->assignedDivisionIds();

        $today = Carbon::today();
        $to = $today->toDateString();
        $from = $today->copy()->subDays(7)->toDateString();

        $managerIds = User::query()
            ->whereIn('division_id', $divisionIds ?: [-1])
            ->where('role', 'manager')
            ->where('status', 'active')
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $scopedUserIds = collect($managerIds)
            ->when($hod, fn ($c) => $c->push((int) $hod->id))
            ->unique()
            ->values()
            ->all();

        $workdays = $this->buildWorkdays($from, $to);

        $required = max(count($scopedUserIds) * max(count($workdays), 1), 1);
        $onTime = DailyEntry::query()
            ->whereIn('user_id', $scopedUserIds ?: [-1])
            ->whereIn('entry_date', $workdays)
            ->whereNotIn('plan_status', ['late', 'missing'])
            ->whereNotIn('realization_status', ['late', 'missing'])
            ->count();
        $onTimeRate = (int) round(($onTime / $required) * 100);

        $healthTodayAvg = 0;
        if (! empty($divisionIds)) {
            $scores = HealthScore::query()
                ->where('scope_type', 'division')
                ->whereIn('scope_id', $divisionIds)
                ->whereDate('score_date', $to)
                ->pluck('score');
            $healthTodayAvg = $scores->isNotEmpty() ? (int) round($scores->avg()) : 0;
        }

        $findingsToday = Finding::query()
            ->whereDate('finding_date', $to)
            ->whereIn('division_id', $divisionIds ?: [-1])
            ->selectRaw("sum(case when severity='high' then 1 else 0 end) as high_count")
            ->selectRaw("sum(case when severity='medium' then 1 else 0 end) as medium_count")
            ->selectRaw("sum(case when severity='low' then 1 else 0 end) as low_count")
            ->first();

        $findings7d = Finding::query()
            ->whereBetween('finding_date', [$from, $to])
            ->whereIn('division_id', $divisionIds ?: [-1])
            ->selectRaw("sum(case when severity='high' then 1 else 0 end) as high_count")
            ->selectRaw("sum(case when severity='medium' then 1 else 0 end) as medium_count")
            ->selectRaw("sum(case when severity='low' then 1 else 0 end) as low_count")
            ->first();

        $topMissing = Finding::query()
            ->whereBetween('finding_date', [$from, $to])
            ->whereIn('division_id', $divisionIds ?: [-1])
            ->where('type', 'missing_daily')
            ->whereIn('user_id', $managerIds ?: [-1])
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topRisk = Finding::query()
            ->whereBetween('finding_date', [$from, $to])
            ->whereIn('division_id', $divisionIds ?: [-1])
            ->whereIn('severity', ['medium', 'high'])
            ->whereIn('user_id', $managerIds ?: [-1])
            ->selectRaw("user_id,
                sum(case when severity='high' then 1 else 0 end) as high_count,
                sum(case when severity='medium' then 1 else 0 end) as medium_count,
                count(*) as total")
            ->groupBy('user_id')
            ->orderByDesc('high_count')
            ->orderByDesc('medium_count')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $userIds = collect([$topMissing->pluck('user_id'), $topRisk->pluck('user_id')])
            ->flatten()
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $userNames = User::query()
            ->whereIn('id', $userIds ?: [-1])
            ->pluck('name', 'id')
            ->all();

        $topMissingManagers = $topMissing->map(fn ($r) => [
            'manager' => $userNames[$r->user_id] ?? '—',
            'missing_days' => (int) $r->total,
        ])->all();

        $topRiskManagers = $topRisk->map(fn ($r) => [
            'manager' => $userNames[$r->user_id] ?? '—',
            'high' => (int) ($r->high_count ?? 0),
            'medium' => (int) ($r->medium_count ?? 0),
            'medium_high_total' => (int) ($r->total ?? 0),
        ])->all();

        return [
            'scope' => [
                'division_ids' => $divisionIds,
                'manager_user_ids' => $managerIds,
            ],
            'date_range' => ['from' => $from, 'to' => $to],
            'division_health_score_today_avg' => $healthTodayAvg,
            'on_time_reporting_rate_7d_percent' => $onTimeRate,
            'findings_today' => [
                'high' => (int) ($findingsToday?->high_count ?? 0),
                'medium' => (int) ($findingsToday?->medium_count ?? 0),
                'low' => (int) ($findingsToday?->low_count ?? 0),
            ],
            'findings_7d' => [
                'high' => (int) ($findings7d?->high_count ?? 0),
                'medium' => (int) ($findings7d?->medium_count ?? 0),
                'low' => (int) ($findings7d?->low_count ?? 0),
            ],
            'top_managers_by_missing_daily_7d' => $topMissingManagers,
            'top_managers_by_medium_high_findings_7d' => $topRiskManagers,
            'notes' => [
                'Data bersumber dari sistem (daily entry + findings + health score).',
                'Scope dibatasi pada divisi HoD.',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.hod.ai-chat-page', [
            'suggestedPrompts' => $this->suggestedPrompts,
            'chatMessages' => $this->messages,
        ])->layout('components.layouts.app', [
            'title' => 'AI Chat',
        ]);
    }
}
