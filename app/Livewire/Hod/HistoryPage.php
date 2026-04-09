<?php

namespace App\Livewire\Hod;

use App\Models\DailyEntry;
use App\Models\DailyEntryItem;
use App\Models\DailyEntryItemAttachment;
use App\Models\Finding;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class HistoryPage extends Component
{
    public ?string $from = null;
    public ?string $to = null;

    public bool $drawerOpen = false;
    public ?int $selectedItemId = null;

    /** @var array<string, mixed> */
    public array $selectedItem = [];

    /** @var array<int, array<string, mixed>> */
    public array $selectedAttachments = [];

    public function mount(): void
    {
        $today = Carbon::today();

        $this->from = request('from') ?: $today->copy()->subDays(7)->toDateString();
        $this->to = request('to') ?: $today->toDateString();

        $this->normalizeDates();
    }

    public function applyFilters(): void
    {
        $this->validate([
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $this->normalizeDates();
        $this->closeDrawer();
    }

    protected function normalizeDates(): void
    {
        try {
            $from = Carbon::parse($this->from)->startOfDay();
            $to = Carbon::parse($this->to)->startOfDay();
        } catch (\Throwable) {
            $today = Carbon::today();
            $from = $today->copy()->subDays(7);
            $to = $today;
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        // Batasi range biar tetap ringan.
        if ($to->diffInDays($from) > 60) {
            $from = $to->copy()->subDays(60);
        }

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    public function openDetail(int $dailyEntryItemId): void
    {
        $user = auth()->user();

        $item = DailyEntryItem::query()
            ->with([
                'entry:id,user_id,entry_date,plan_status,realization_status,plan_submitted_at,realization_submitted_at',
                'bigRock:id,title',
                'roadmapItem:id,title',
            ])
            ->where('id', $dailyEntryItemId)
            ->whereHas('entry', fn ($q) => $q->where('user_id', $user->id))
            ->first();

        if (! $item) {
            return;
        }

        // MVP: findings tidak terhubung langsung ke item, jadi ambil severity maksimum per hari untuk user ini.
        $findingSeverity = 0;
        if ($item->entry?->entry_date) {
            $findingSeverity = (int) (Finding::query()
                ->where('user_id', $user->id)
                ->whereDate('finding_date', Carbon::parse($item->entry->entry_date)->toDateString())
                ->selectRaw("max(case when severity='high' then 3 when severity='medium' then 2 when severity='low' then 1 else 0 end) as sev")
                ->value('sev') ?? 0);
        }

        $severity = null;
        if ((int) $findingSeverity >= 3) $severity = 'major';
        elseif ((int) $findingSeverity === 2) $severity = 'medium';
        elseif ((int) $findingSeverity === 1) $severity = 'minor';

        $this->selectedItemId = (int) $item->id;
        $this->selectedItem = [
            'date' => $item->entry?->entry_date ? Carbon::parse($item->entry->entry_date)->translatedFormat('j M Y') : '—',
            'title' => $item->plan_title ?: '—',
            'plan_text' => $item->plan_text ?: '',
            'plan_relation_reason' => $item->plan_relation_reason ?: '',
            'big_rock' => $item->bigRock?->title ?? '—',
            'roadmap' => $item->roadmapItem?->title ?? '—',
            'plan_status' => $item->entry?->plan_status ?: 'missing',
            'realization_status' => $item->entry?->realization_status ?: 'missing',
            'realization_text' => $item->realization_text ?: '',
            'realization_reason' => $item->realization_reason ?: '',
            'severity' => $severity,
        ];

        $attachments = DailyEntryItemAttachment::query()
            ->where('daily_entry_item_id', $item->id)
            ->orderBy('id')
            ->get(['id', 'path', 'original_name', 'mime_type', 'size_bytes']);

        $this->selectedAttachments = $attachments->map(function (DailyEntryItemAttachment $a) {
            $url = null;
            try {
                $url = Storage::url($a->path);
            } catch (\Throwable) {
                $url = null;
            }

            return [
                'id' => (int) $a->id,
                'name' => $a->original_name ?: 'file',
                'size_kb' => $a->size_bytes ? (int) ceil($a->size_bytes / 1024) : null,
                'url' => $url,
            ];
        })->all();

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->selectedItemId = null;
        $this->selectedItem = [];
        $this->selectedAttachments = [];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function buildTimeline(): array
    {
        $user = auth()->user();
        $from = Carbon::parse($this->from)->toDateString();
        $to = Carbon::parse($this->to)->toDateString();

        $entries = DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('entry_date', [$from, $to])
            ->orderByDesc('entry_date')
            ->get(['id', 'entry_date', 'plan_status', 'realization_status']);

        if ($entries->isEmpty()) {
            return [];
        }

        $entryIds = $entries->pluck('id')->all();

        $items = DailyEntryItem::query()
            ->with(['bigRock:id,title', 'roadmapItem:id,title'])
            ->whereIn('daily_entry_id', $entryIds)
            ->whereNotNull('plan_title')
            ->orderByDesc('id')
            ->get();

        $severityByDate = Finding::query()
            ->where('user_id', $user->id)
            ->whereBetween('finding_date', [$from, $to])
            ->selectRaw("finding_date, max(case when severity='high' then 3 when severity='medium' then 2 when severity='low' then 1 else 0 end) as sev")
            ->groupBy('finding_date')
            ->pluck('sev', 'finding_date')
            ->all();

        $entryById = $entries->keyBy('id');
        $grouped = [];

        foreach ($items as $item) {
            $entry = $entryById[$item->daily_entry_id] ?? null;
            if (! $entry) continue;

            $dateKey = Carbon::parse($entry->entry_date)->translatedFormat('j M Y (l)');
            $sev = (int) ($severityByDate[Carbon::parse($entry->entry_date)->toDateString()] ?? 0);
            $severity = $sev >= 3 ? 'major' : ($sev === 2 ? 'medium' : ($sev === 1 ? 'minor' : null));

            $grouped[$dateKey] ??= [];
            $grouped[$dateKey][] = [
                'id' => (int) $item->id,
                'title' => $item->plan_title,
                'big_rock' => $item->bigRock?->title ?? '—',
                'roadmap' => $item->roadmapItem?->title ?? '—',
                'plan_status' => $entry->plan_status ?: 'missing',
                'realization_status' => $entry->realization_status ?: 'missing',
                'severity' => $severity,
            ];
        }

        return $grouped;
    }

    public function render()
    {
        return view('livewire.hod.history-page', [
            'historyByDate' => $this->buildTimeline(),
        ])->layout('components.layouts.app', [
            'title' => 'Riwayat Entry',
        ]);
    }
}

