<?php

namespace App\Livewire\Hod;

use App\Models\DailyEntry;
use App\Models\Finding;
use App\Models\HodAssignment;
use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Entry Divisi')]
class DivisionEntriesPage extends Component
{
    #[Url]
    public string $divisionId = '';

    #[Url]
    public ?string $date = null;

    #[Url]
    public string $user = '';

    public bool $findingsOnly = false;

    public bool $drawerOpen = false;
    public ?int $selectedUserId = null;

    /** @var array<string, mixed> */
    public array $selected = [];

    /** @var array<int, array<string, mixed>> */
    public array $selectedItems = [];

    /** @var array<int, array<string, mixed>> */
    public array $selectedFindings = [];

    public function mount(): void
    {
        $this->date = $this->date ?: Carbon::today()->toDateString();
        $this->normalizeDate();

        // Default: jika HoD pegang lebih dari 1 divisi, pilih yang pertama.
        $ids = $this->assignedDivisionIds();
        if ($this->divisionId === '' && ! empty($ids)) {
            $this->divisionId = (string) $ids[0];
        }
    }

    public function applyFilters(): void
    {
        $this->validate([
            'date' => 'required|date',
        ]);

        $this->normalizeDivisionId();
        $this->normalizeDate();
        $this->closeDrawer();
    }

    protected function normalizeDivisionId(): void
    {
        if ($this->divisionId === '') {
            return;
        }

        $allowed = $this->assignedDivisionIds();
        if (! in_array((int) $this->divisionId, $allowed, true)) {
            $this->divisionId = ! empty($allowed) ? (string) $allowed[0] : '';
        }
    }

    protected function normalizeDate(): void
    {
        try {
            $d = Carbon::parse($this->date)->startOfDay();
        } catch (\Throwable) {
            $d = Carbon::today();
        }

        // Batasi agar tidak terlalu jauh (UX + beban query).
        $min = Carbon::today()->subDays(365);
        $max = Carbon::today()->addDays(7);
        if ($d->lt($min)) $d = $min;
        if ($d->gt($max)) $d = $max;

        $this->date = $d->toDateString();
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
     * @return array<int, array{id:int,name:string}>
     */
    protected function divisionOptions(array $divisionIds): array
    {
        if (empty($divisionIds)) {
            return [];
        }

        return Division::query()
            ->whereIn('id', $divisionIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['id' => (int) $d->id, 'name' => (string) $d->name])
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    protected function managerOptions(array $divisionIds): array
    {
        if (empty($divisionIds)) {
            return [];
        }

        return User::query()
            ->whereIn('division_id', $divisionIds)
            ->where('role', 'manager')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => (int) $u->id, 'name' => (string) $u->name])
            ->all();
    }

    protected function severityRank(?string $severity): int
    {
        return match ($severity) {
            'high', 'major' => 3,
            'medium' => 2,
            'low', 'minor' => 1,
            default => 0,
        };
    }

    /**
     * @param array<int, array{severity:string}> $rows
     */
    protected function maxSeverity(array $rows): ?string
    {
        $max = 0;
        $selected = null;

        foreach ($rows as $r) {
            $rank = $this->severityRank($r['severity'] ?? null);
            if ($rank > $max) {
                $max = $rank;
                $selected = (string) ($r['severity'] ?? null);
            }
        }

        return $selected;
    }

    protected function normalizeSeverity(?string $severity): ?string
    {
        if (! $severity) {
            return null;
        }

        $severity = strtolower(trim($severity));
        $allowed = ['low', 'medium', 'high', 'minor', 'major'];

        return in_array($severity, $allowed, true) ? $severity : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildRows(array $divisionIds): array
    {
        if (empty($divisionIds)) {
            return [];
        }

        $date = Carbon::parse($this->date)->toDateString();

        $divisionIds = $this->divisionId !== '' ? [(int) $this->divisionId] : $divisionIds;

        $managersQuery = User::query()
            ->whereIn('division_id', $divisionIds)
            ->where('role', 'manager')
            ->where('status', 'active')
            ->orderBy('name');

        if ($this->user !== '') {
            $managersQuery->where('id', (int) $this->user);
        }

        $managers = $managersQuery->get(['id', 'name', 'division_id']);
        if ($managers->isEmpty()) {
            return [];
        }

        $userIds = $managers->pluck('id')->map(fn ($v) => (int) $v)->values()->all();

        $entriesByUserId = DailyEntry::query()
            ->with([
                'items' => fn ($q) => $q->orderBy('id')->with(['bigRock:id,title', 'roadmapItem:id,title']),
            ])
            ->whereIn('user_id', $userIds)
            ->whereDate('entry_date', $date)
            ->get(['id', 'user_id', 'entry_date', 'plan_status', 'realization_status', 'plan_submitted_at', 'realization_submitted_at'])
            ->keyBy('user_id');

        $findingsByUserId = Finding::query()
            ->whereDate('finding_date', $date)
            ->whereIn('user_id', $userIds)
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'severity', 'title', 'type'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => [
                'id' => (int) $r->id,
                'severity' => (string) $r->severity,
                'title' => (string) $r->title,
                'type' => (string) $r->type,
            ])->all())
            ->all();

        $rows = [];

        foreach ($managers as $mgr) {
            /** @var DailyEntry|null $entry */
            $entry = $entriesByUserId->get($mgr->id);

            $planStatus = $entry?->plan_status ?: 'missing';
            $realStatus = $entry?->realization_status ?: 'missing';

            $firstItem = $entry?->items?->first();

            $baseFindings = $findingsByUserId[$mgr->id] ?? [];
            $missingAsFinding = ($planStatus === 'missing' || $realStatus === 'missing');
            $severity = $this->normalizeSeverity($this->maxSeverity($baseFindings));
            if ($missingAsFinding && $this->severityRank($severity) < 2) {
                $severity = 'medium';
            }

            $hasFinding = $missingAsFinding || ! empty($baseFindings);

            if ($this->findingsOnly && ! $hasFinding) {
                continue;
            }

            $rows[] = [
                'user_id' => (int) $mgr->id,
                'user' => (string) $mgr->name,
                'date' => Carbon::parse($date)->translatedFormat('j M Y'),
                'title' => $firstItem?->plan_title ?: '-',
                'big_rock' => $firstItem?->bigRock?->title ?: '-',
                'roadmap' => $firstItem?->roadmapItem?->title ?: '-',
                'plan_status' => (string) $planStatus,
                'realization_status' => (string) $realStatus,
                'has_finding' => $hasFinding,
                'severity' => $severity,
                'finding_count' => count($baseFindings) + ($missingAsFinding ? 1 : 0),
            ];
        }

        return $rows;
    }

    public function openDetail(int $userId): void
    {
        $divisionIds = $this->assignedDivisionIds();
        if (empty($divisionIds)) {
            return;
        }

        $date = Carbon::parse($this->date)->toDateString();

        $mgr = User::query()
            ->where('id', $userId)
            ->where('role', 'manager')
            ->where('status', 'active')
            ->whereIn('division_id', $divisionIds)
            ->first(['id', 'name', 'division_id']);

        if (! $mgr) {
            return;
        }

        $entry = DailyEntry::query()
            ->with([
                'items' => fn ($q) => $q->orderBy('id')->with([
                    'bigRock:id,title',
                    'roadmapItem:id,title',
                    'attachments:id,daily_entry_item_id,path,original_name,size_bytes',
                ]),
            ])
            ->where('user_id', $mgr->id)
            ->whereDate('entry_date', $date)
            ->first(['id', 'user_id', 'entry_date', 'plan_status', 'realization_status', 'plan_submitted_at', 'realization_submitted_at']);

        $planStatus = $entry?->plan_status ?: 'missing';
        $realStatus = $entry?->realization_status ?: 'missing';

        $findings = Finding::query()
            ->where('user_id', $mgr->id)
            ->whereDate('finding_date', $date)
            ->orderByDesc('id')
            ->get(['id', 'severity', 'title', 'description', 'type'])
            ->map(fn ($f) => [
                'id' => (int) $f->id,
                'severity' => (string) $f->severity,
                'title' => (string) $f->title,
                'description' => (string) ($f->description ?: ''),
                'type' => (string) $f->type,
            ])
            ->all();

        $missingAsFinding = ($planStatus === 'missing' || $realStatus === 'missing');
        $severity = $this->normalizeSeverity($this->maxSeverity($findings));
        if ($missingAsFinding && $this->severityRank($severity) < 2) {
            $severity = 'medium';
        }

        $this->selectedUserId = (int) $mgr->id;
        $this->selected = [
            'user' => (string) $mgr->name,
            'date' => Carbon::parse($date)->translatedFormat('j M Y (l)'),
            'plan_status' => (string) $planStatus,
            'realization_status' => (string) $realStatus,
            'severity' => $severity,
            'plan_submitted_at' => $entry?->plan_submitted_at?->translatedFormat('j M Y, H:i'),
            'realization_submitted_at' => $entry?->realization_submitted_at?->translatedFormat('j M Y, H:i'),
        ];

        $this->selectedFindings = $findings;

        if (! $entry) {
            $this->selectedItems = [];
            $this->drawerOpen = true;
            return;
        }

        $this->selectedItems = $entry->items->map(function ($item) {
            $attachments = $item->attachments->map(function ($a) {
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

            return [
                'id' => (int) $item->id,
                'title' => $item->plan_title ?: '-',
                'big_rock' => $item->bigRock?->title ?? '-',
                'roadmap' => $item->roadmapItem?->title ?? '-',
                'plan_text' => $item->plan_text ?: '',
                'plan_relation_reason' => $item->plan_relation_reason ?: '',
                'plan_duration_minutes' => $item->plan_duration_minutes !== null ? (int) $item->plan_duration_minutes : null,
                'realization_status' => $item->realization_status ?: 'draft',
                'realization_text' => $item->realization_text ?: '',
                'realization_reason' => $item->realization_reason ?: '',
                'realization_duration_minutes' => $item->realization_duration_minutes !== null ? (int) $item->realization_duration_minutes : null,
                'attachments' => $attachments,
            ];
        })->all();

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->selectedUserId = null;
        $this->selected = [];
        $this->selectedItems = [];
        $this->selectedFindings = [];
    }

    public function render()
    {
        $divisionIds = $this->assignedDivisionIds();
        $this->normalizeDivisionId();

        $filteredDivisionIds = $this->divisionId !== '' ? [(int) $this->divisionId] : $divisionIds;
        $managerOptions = $this->managerOptions($filteredDivisionIds);

        return view('livewire.hod.division-entries-page', [
            'rows' => $this->buildRows($divisionIds),
            'managerOptions' => $managerOptions,
            'divisionOptions' => $this->divisionOptions($divisionIds),
            'isWeekend' => Carbon::parse($this->date)->isWeekend(),
        ])->layout('components.layouts.app', [
            'title' => 'Entry Divisi',
        ]);
    }
}
