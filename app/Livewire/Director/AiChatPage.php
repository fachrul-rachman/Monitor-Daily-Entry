<?php

namespace App\Livewire\Director;

use App\Models\DailyEntry;
use App\Models\Finding;
use App\Models\HealthScore;
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
        'Divisi mana yang paling banyak exception minggu ini?',
        'Siapa saja yang berulang kali missing submission?',
        'Berikan insight kesehatan Divisi Operasional 7 hari terakhir',
        'Apa pola umum exception di perusahaan?',
        'Bandingkan performa semua divisi 7 hari terakhir',
    ];

    public function mount(): void
    {
        $this->messages = [
            [
                'role' => 'ai',
                'content' => 'Silakan tanyakan kondisi hari ini atau 7 hari terakhir. Saya akan jawab ringkas dan to the point.',
                'points' => [
                    'Contoh: "Divisi mana paling banyak temuan minggu ini?"',
                    'Contoh: "Apa 3 temuan terbesar hari ini?"',
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
        } catch (\Throwable $e) {
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
You are "Dayta AI", an executive assistant for directors.

Your goals:
- Answer in a concise, decision-ready way.
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

        // Remove common markdown fences if present.
        $t = preg_replace('/^```(?:json)?\\s*/i', '', $t) ?? $t;
        $t = preg_replace('/\\s*```\\s*$/', '', $t) ?? $t;

        // Find first JSON object block.
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
                if ($ch === '\\\\') {
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
     * @return array<string, mixed>
     */
    protected function buildContextSnapshot(): array
    {
        $today = Carbon::today();
        $to = $today->toDateString();
        $from = $today->copy()->subDays(7)->toDateString();

        $healthToday = (int) (HealthScore::query()
            ->where('scope_type', 'company')
            ->whereDate('score_date', $to)
            ->value('score') ?? 0);

        $findingsToday = Finding::query()
            ->whereDate('finding_date', $to)
            ->selectRaw("sum(case when severity='high' then 1 else 0 end) as high_count")
            ->selectRaw("sum(case when severity='medium' then 1 else 0 end) as medium_count")
            ->selectRaw("sum(case when severity='low' then 1 else 0 end) as low_count")
            ->first();

        $findings7d = Finding::query()
            ->whereBetween('finding_date', [$from, $to])
            ->selectRaw("sum(case when severity='high' then 1 else 0 end) as high_count")
            ->selectRaw("sum(case when severity='medium' then 1 else 0 end) as medium_count")
            ->selectRaw("sum(case when severity='low' then 1 else 0 end) as low_count")
            ->first();

        $topDivisions = Finding::query()
            ->whereBetween('finding_date', [$from, $to])
            ->whereIn('severity', ['medium', 'high'])
            ->whereNotNull('division_id')
            ->selectRaw('division_id, count(*) as total')
            ->groupBy('division_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $divisionNames = \App\Models\Division::query()
            ->whereIn('id', $topDivisions->pluck('division_id')->all())
            ->pluck('name', 'id')
            ->all();

        $topDivisionPayload = $topDivisions->map(fn ($r) => [
            'division' => $divisionNames[$r->division_id] ?? '—',
            'medium_high_findings' => (int) $r->total,
        ])->all();

        $activeUserIds = User::query()
            ->whereIn('role', ['hod', 'manager'])
            ->where('status', 'active')
            ->pluck('id')
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

        $required = max(count($activeUserIds) * max(count($workdays), 1), 1);
        $onTime = DailyEntry::query()
            ->whereIn('user_id', $activeUserIds)
            ->whereIn('entry_date', $workdays)
            ->whereNotIn('plan_status', ['late', 'missing'])
            ->whereNotIn('realization_status', ['late', 'missing'])
            ->count();

        $onTimeRate = (int) round(($onTime / $required) * 100);

        return [
            'date_range' => ['from' => $from, 'to' => $to],
            'company_health_score_today' => $healthToday,
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
            'top_divisions_by_medium_high_findings_7d' => $topDivisionPayload,
            'notes' => [
                'Findings are system-generated. Manual editing by HoD is not allowed (MVP rule).',
                'If a question needs deeper breakdown, ask one clarification question.',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.director.ai-chat-page', [
            'suggestedPrompts' => $this->suggestedPrompts,
            'chatMessages' => $this->messages,
        ])->layout('components.layouts.app', [
            'title' => 'AI Chat',
        ]);
    }
}
