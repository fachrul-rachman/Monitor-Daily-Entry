<?php

namespace App\Livewire\Hod;

use App\Models\DailyEntry;
use App\Models\Finding;
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

            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                $rawJson = $this->extractJsonObject($raw);
                if ($rawJson) {
                    $decoded = json_decode($rawJson, true);
                }
            }

            if (is_array($decoded) && isset($decoded['answer_id'], $decoded['answer_en'])) {
                $points = [];
                if (! empty($decoded['bullets']) && is_array($decoded['bullets'])) {
                    $points = array_values(array_filter(array_map('strval', $decoded['bullets'])));
                }

                $content = trim((string) $decoded['answer_id']);
                $en = trim((string) $decoded['answer_en']);
                if ($en !== '') {
                    $content .= "\n\nEN: ".$en;
                }

                $this->messages[] = [
                    'role' => 'ai',
                    'content' => $content,
                    'points' => $points,
                ];

                return;
            }
        } catch (\Throwable) {
            // fallthrough
        }

        $this->messages[] = [
            'role' => 'ai',
            'content' => 'Maaf, AI sedang bermasalah. Coba lagi beberapa saat, atau cek API key/model yang dipasang.',
            'points' => [
                'Kalau ini terjadi terus, berarti koneksi ke AI belum siap.',
            ],
        ];
    }

    protected function buildSystemMessage(): string
    {
        return <<<SYS
You are "Dayta AI", an operational assistant for a Head of Division (HoD).

Rules:
- You ONLY analyze the HoD's division scope provided in the context.
- Be concise and action-oriented (coaching / follow-up ready).
- Output bilingual: Indonesian + English.
- Keep it short: maximum 6 bullets when relevant.
- If the data is insufficient, say so briefly and ask up to 1 clarification question.

Output format MUST be valid JSON only (no markdown, no extra text):
{
  "answer_id": "string",
  "answer_en": "string",
  "bullets": ["string", "..."]
}
SYS;
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

        $workdays = [];
        $cursor = Carbon::parse($from);
        $toDate = Carbon::parse($to);
        while ($cursor->lte($toDate)) {
            if (! $cursor->isWeekend()) {
                $workdays[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

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

        $topUsers = Finding::query()
            ->whereBetween('finding_date', [$from, $to])
            ->whereIn('division_id', $divisionIds ?: [-1])
            ->whereIn('severity', ['medium', 'high'])
            ->whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $userNames = User::query()
            ->whereIn('id', $topUsers->pluck('user_id')->all())
            ->pluck('name', 'id')
            ->all();

        $topUserPayload = $topUsers->map(fn ($r) => [
            'user' => $userNames[$r->user_id] ?? '—',
            'medium_high_findings' => (int) $r->total,
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
            'top_users_by_medium_high_findings_7d' => $topUserPayload,
            'notes' => [
                'Findings are system-generated. Manual editing by HoD is not allowed (MVP rule).',
                'Workdays are Monday–Friday (weekends excluded).',
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

