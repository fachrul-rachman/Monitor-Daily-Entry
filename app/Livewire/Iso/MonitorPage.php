<?php

namespace App\Livewire\Iso;

use App\Models\DailyEntry;
use App\Models\DailyEntryItem;
use App\Models\BigRock;
use App\Models\Division;
use App\Models\Finding;
use App\Models\DailyEntryItemAttachment;
use App\Models\RoadmapItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('ISO Monitor')]
class MonitorPage extends Component
{
    use WithPagination;

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    #[Url]
    public string $division = '';

    #[Url]
    public string $role = ''; // hod | manager | ''

    #[Url]
    public string $status = ''; // missing | late | finding_high | finding_medium | ok | ''

    #[Url]
    public string $search = '';

    public bool $drawerOpen = false;
    public ?int $selectedUserId = null;

    /** @var array<string, mixed> */
    public array $selectedUser = [];

    /** @var array<int, array<string, mixed>> */
    public array $selectedDays = [];

    /** @var array<int, array<string, mixed>> */
    public array $selectedItems = [];

    /** @var array<int, array<string, mixed>> */
    public array $selectedFindings = [];

    /** @var array<int, array<string, mixed>> */
    public array $selectedBigRocks = [];

    public function mount(): void
    {
        $today = Carbon::today();
        $this->to = $this->to ?: $today->toDateString();
        $this->from = $this->from ?: $today->copy()->subDays(7)->toDateString();
        $this->normalizeDates();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingDivision(): void { $this->resetPage(); }
    public function updatingRole(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingFrom(): void { $this->resetPage(); }
    public function updatingTo(): void { $this->resetPage(); }

    public function applyFilters(): void
    {
        $this->validate([
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $this->normalizeDates();
        $this->resetPage();
        $this->closeDrawer();
    }

    public function resetFilters(): void
    {
        $today = Carbon::today();
        $this->reset('division', 'role', 'status', 'search');
        $this->to = $today->toDateString();
        $this->from = $today->copy()->subDays(7)->toDateString();
        $this->normalizeDates();
        $this->resetPage();
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

        // Batasi agar tetap ringan (maks 60 hari).
        if ($to->diffInDays($from) > 60) {
            $from = $to->copy()->subDays(60);
        }

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    public function openDetail(int $userId): void
    {
        $from = Carbon::parse($this->from)->toDateString();
        $to = Carbon::parse($this->to)->toDateString();

        $user = User::query()
            ->with(['division:id,name'])
            ->where('id', $userId)
            ->whereIn('role', ['hod', 'manager'])
            ->where('status', 'active')
            ->first(['id', 'name', 'email', 'role', 'division_id']);

        if (! $user) {
            return;
        }

        $this->selectedUserId = (int) $user->id;
        $this->selectedUser = [
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'role' => (string) $user->role,
            'division' => (string) ($user->division?->name ?? '—'),
            'from' => Carbon::parse($from)->translatedFormat('j M Y'),
            'to' => Carbon::parse($to)->translatedFormat('j M Y'),
        ];

        $entries = DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('entry_date', [$from, $to])
            ->orderByDesc('entry_date')
            ->get(['id', 'entry_date', 'plan_status', 'realization_status', 'plan_submitted_at', 'realization_submitted_at']);

        $entryIds = $entries->pluck('id')->all();

        $this->selectedDays = $entries->map(function (DailyEntry $e) {
            $date = $e->entry_date?->toDateString();

            return [
                'id' => (int) $e->id,
                'date' => $date ? Carbon::parse($date)->translatedFormat('j M Y (l)') : '—',
                'plan_status' => (string) ($e->plan_status ?: 'missing'),
                'real_status' => (string) ($e->realization_status ?: 'missing'),
                'plan_submitted_at' => $e->plan_submitted_at?->translatedFormat('j M Y, H:i'),
                'real_submitted_at' => $e->realization_submitted_at?->translatedFormat('j M Y, H:i'),
            ];
        })->all();

        $items = [];
        if (! empty($entryIds)) {
            $items = DailyEntryItem::query()
                ->with(['entry:id,entry_date', 'bigRock:id,title', 'roadmapItem:id,title'])
                ->whereIn('daily_entry_id', $entryIds)
                ->orderByDesc('daily_entry_id')
                ->orderBy('id')
                ->get([
                    'id',
                    'daily_entry_id',
                    'big_rock_id',
                    'roadmap_item_id',
                    'plan_title',
                    'plan_duration_minutes',
                    'realization_status',
                    'realization_duration_minutes',
                ]);
        }

        $attachmentsByItemId = [];
        if (! empty($items)) {
            $itemIds = collect($items)->pluck('id')->map(fn ($v) => (int) $v)->values()->all();

            $attachments = DailyEntryItemAttachment::query()
                ->whereIn('daily_entry_item_id', $itemIds)
                ->orderBy('id')
                ->get(['id', 'daily_entry_item_id', 'path', 'original_name', 'size_bytes']);

            $attachmentsByItemId = $attachments
                ->groupBy('daily_entry_item_id')
                ->map(function ($rows) {
                    return $rows->map(function (DailyEntryItemAttachment $a) {
                        $url = null;
                        try {
                            $url = Storage::url($a->path);
                        } catch (\Throwable) {
                            $url = null;
                        }

                        return [
                            'id' => (int) $a->id,
                            'name' => (string) ($a->original_name ?: basename((string) $a->path)),
                            'size_kb' => $a->size_bytes ? (int) ceil(((int) $a->size_bytes) / 1024) : null,
                            'url' => $url,
                        ];
                    })->values()->all();
                })
                ->all();
        }

        $this->selectedItems = collect($items)->map(function (DailyEntryItem $it) use ($attachmentsByItemId) {
            $planMins = $it->plan_duration_minutes !== null ? (int) $it->plan_duration_minutes : null;
            $realMins = $it->realization_duration_minutes !== null ? (int) $it->realization_duration_minutes : null;

            return [
                'id' => (int) $it->id,
                'date' => $it->entry?->entry_date ? Carbon::parse($it->entry->entry_date)->translatedFormat('j M Y') : '—',
                'title' => (string) ($it->plan_title ?: '-'),
                'big_rock' => (string) ($it->bigRock?->title ?? '-'),
                'roadmap' => (string) ($it->roadmapItem?->title ?? '-'),
                'plan_minutes' => $planMins,
                'real_minutes' => $realMins,
                'real_status' => (string) ($it->realization_status ?: 'draft'),
                'attachments' => $attachmentsByItemId[(int) $it->id] ?? [],
            ];
        })->all();

        // Big Rock + Roadmap (read-only)
        $bigRocks = BigRock::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'archived')
            ->orderByRaw("case when status='active' then 0 when status='on_track' then 1 when status='at_risk' then 2 when status='completed' then 3 when status='archived' then 4 else 5 end")
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'title', 'description', 'status', 'start_date', 'end_date']);

        $roadmapsByBigRock = RoadmapItem::query()
            ->whereIn('big_rock_id', $bigRocks->pluck('id')->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'big_rock_id', 'title', 'status', 'sort_order'])
            ->groupBy('big_rock_id');

        $weights = [
            'planned' => 0.0,
            'in_progress' => 0.6,
            'blocked' => 0.3,
            'finished' => 1.0,
            'completed' => 1.0,
            'done' => 1.0,
        ];

        $this->selectedBigRocks = $bigRocks->map(function (BigRock $br) use ($roadmapsByBigRock, $weights) {
            $roadmaps = ($roadmapsByBigRock[$br->id] ?? collect())
                ->map(fn ($rm) => [
                    'id' => (int) $rm->id,
                    'title' => (string) $rm->title,
                    'status' => (string) ($rm->status ?: 'planned'),
                    'sort_order' => (int) $rm->sort_order,
                ])
                ->values()
                ->all();

            $scoped = collect($roadmaps)->reject(fn ($rm) => ($rm['status'] ?? '') === 'archived')->values();
            $total = $scoped->count();
            $score = $scoped->map(fn ($rm) => (float) ($weights[$rm['status']] ?? 0.0))->sum();
            $progress = $total > 0 ? (int) round(($score / $total) * 100) : 0;

            return [
                'id' => (int) $br->id,
                'title' => (string) $br->title,
                'description' => (string) ($br->description ?: ''),
                'status' => (string) ($br->status ?: 'active'),
                'start' => $br->start_date ? Carbon::parse($br->start_date)->translatedFormat('j M Y') : '—',
                'end' => $br->end_date ? Carbon::parse($br->end_date)->translatedFormat('j M Y') : '—',
                'progress' => $progress,
                'roadmaps' => $roadmaps,
            ];
        })->all();

        $this->selectedFindings = Finding::query()
            ->where('scope_type', 'user')
            ->where('scope_id', $user->id)
            ->whereBetween('finding_date', [$from, $to])
            ->orderByRaw("case when severity='high' then 0 when severity='medium' then 1 when severity='low' then 2 else 3 end")
            ->orderByDesc('id')
            ->get(['id', 'finding_date', 'severity', 'type', 'title'])
            ->map(fn ($f) => [
                'id' => (int) $f->id,
                'date' => $f->finding_date ? Carbon::parse($f->finding_date)->translatedFormat('j M Y') : '—',
                'severity' => (string) $f->severity,
                'type' => (string) $f->type,
                'title' => (string) $f->title,
            ])
            ->all();

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->selectedUserId = null;
        $this->selectedUser = [];
        $this->selectedDays = [];
        $this->selectedItems = [];
        $this->selectedBigRocks = [];
        $this->selectedFindings = [];
    }

    public function render()
    {
        $from = Carbon::parse($this->from)->toDateString();
        $to = Carbon::parse($this->to)->toDateString();

        $usersQ = User::query()
            ->whereIn('role', ['hod', 'manager'])
            ->where('status', 'active');

        if ($this->division !== '') {
            $usersQ->where('division_id', (int) $this->division);
        }

        if ($this->role !== '') {
            $usersQ->where('role', $this->role);
        }

        if (trim($this->search) !== '') {
            $s = trim($this->search);
            $usersQ->where(function ($q) use ($s) {
                $q->where('name', 'ilike', '%'.$s.'%')
                    ->orWhere('email', 'ilike', '%'.$s.'%');
            });
        }

        $users = $usersQ
            ->with(['division:id,name'])
            ->orderBy('name')
            ->paginate(20);

        $userIds = $users->pluck('id')->map(fn ($v) => (int) $v)->values()->all();

        $lateByUser = [];
        $missingByUser = [];
        $findHighByUser = [];
        $findMedByUser = [];
        $touchedBigRocksByUser = [];
        $touchedRoadmapsByUser = [];

        if (! empty($userIds)) {
            $lateByUser = DailyEntry::query()
                ->whereIn('user_id', $userIds)
                ->whereBetween('entry_date', [$from, $to])
                ->selectRaw("user_id, sum(case when plan_status='late' then 1 else 0 end) as plan_late")
                ->selectRaw("sum(case when realization_status='late' then 1 else 0 end) as real_late")
                ->groupBy('user_id')
                ->get()
                ->mapWithKeys(fn ($r) => [(int) $r->user_id => ['plan' => (int) $r->plan_late, 'real' => (int) $r->real_late]])
                ->all();

            $missingByUser = Finding::query()
                ->where('scope_type', 'user')
                ->whereIn('scope_id', $userIds)
                ->whereBetween('finding_date', [$from, $to])
                ->where('type', 'missing_daily')
                ->selectRaw('scope_id as user_id, count(*) as c')
                ->groupBy('scope_id')
                ->pluck('c', 'user_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $findHighByUser = Finding::query()
                ->where('scope_type', 'user')
                ->whereIn('scope_id', $userIds)
                ->whereBetween('finding_date', [$from, $to])
                ->where('severity', 'high')
                ->selectRaw('scope_id as user_id, count(*) as c')
                ->groupBy('scope_id')
                ->pluck('c', 'user_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $findMedByUser = Finding::query()
                ->where('scope_type', 'user')
                ->whereIn('scope_id', $userIds)
                ->whereBetween('finding_date', [$from, $to])
                ->where('severity', 'medium')
                ->selectRaw('scope_id as user_id, count(*) as c')
                ->groupBy('scope_id')
                ->pluck('c', 'user_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $touchedBigRocksByUser = DailyEntryItem::query()
                ->join('daily_entries', 'daily_entries.id', '=', 'daily_entry_items.daily_entry_id')
                ->whereIn('daily_entries.user_id', $userIds)
                ->whereBetween('daily_entries.entry_date', [$from, $to])
                ->whereNotNull('daily_entry_items.big_rock_id')
                ->selectRaw('daily_entries.user_id as user_id, count(distinct daily_entry_items.big_rock_id) as c')
                ->groupBy('daily_entries.user_id')
                ->pluck('c', 'user_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $touchedRoadmapsByUser = DailyEntryItem::query()
                ->join('daily_entries', 'daily_entries.id', '=', 'daily_entry_items.daily_entry_id')
                ->whereIn('daily_entries.user_id', $userIds)
                ->whereBetween('daily_entries.entry_date', [$from, $to])
                ->whereNotNull('daily_entry_items.roadmap_item_id')
                ->selectRaw('daily_entries.user_id as user_id, count(distinct daily_entry_items.roadmap_item_id) as c')
                ->groupBy('daily_entries.user_id')
                ->pluck('c', 'user_id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $rows = $users->map(function (User $u) use ($lateByUser, $missingByUser, $findHighByUser, $findMedByUser, $touchedBigRocksByUser, $touchedRoadmapsByUser) {
            $late = $lateByUser[$u->id] ?? ['plan' => 0, 'real' => 0];
            $missing = (int) ($missingByUser[$u->id] ?? 0);
            $high = (int) ($findHighByUser[$u->id] ?? 0);
            $med = (int) ($findMedByUser[$u->id] ?? 0);

            $anyLate = ((int) ($late['plan'] ?? 0)) + ((int) ($late['real'] ?? 0));

            $label = 'OK';
            if ($high > 0) $label = 'High';
            elseif ($missing > 0) $label = 'Missing';
            elseif ($anyLate > 0) $label = 'Late';
            elseif ($med > 0) $label = 'Medium';

            return [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'role' => (string) $u->role,
                'division' => (string) ($u->division?->name ?? '—'),
                'missing' => $missing,
                'late_plan' => (int) ($late['plan'] ?? 0),
                'late_real' => (int) ($late['real'] ?? 0),
                'find_high' => $high,
                'find_med' => $med,
                'touched_big_rocks' => (int) ($touchedBigRocksByUser[$u->id] ?? 0),
                'touched_roadmaps' => (int) ($touchedRoadmapsByUser[$u->id] ?? 0),
                'label' => $label,
            ];
        })->filter(function ($row) {
            if ($this->status === '') {
                return true;
            }

            return match ($this->status) {
                'missing' => ((int) ($row['missing'] ?? 0)) > 0,
                'late' => (((int) ($row['late_plan'] ?? 0)) + ((int) ($row['late_real'] ?? 0))) > 0,
                'finding_high' => ((int) ($row['find_high'] ?? 0)) > 0,
                'finding_medium' => ((int) ($row['find_med'] ?? 0)) > 0,
                'ok' => ((int) ($row['missing'] ?? 0)) === 0
                    && (((int) ($row['late_plan'] ?? 0)) + ((int) ($row['late_real'] ?? 0))) === 0
                    && ((int) ($row['find_high'] ?? 0)) === 0
                    && ((int) ($row['find_med'] ?? 0)) === 0,
                default => true,
            };
        })->values()->all();

        $summary = [
            'missing' => collect($rows)->sum(fn ($r) => (int) ($r['missing'] ?? 0)),
            'late' => collect($rows)->sum(fn ($r) => ((int) ($r['late_plan'] ?? 0)) + ((int) ($r['late_real'] ?? 0))),
            'high' => collect($rows)->sum(fn ($r) => (int) ($r['find_high'] ?? 0)),
            'medium' => collect($rows)->sum(fn ($r) => (int) ($r['find_med'] ?? 0)),
        ];

        $divisionOptions = Division::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Division $d) => ['id' => (int) $d->id, 'name' => (string) $d->name])
            ->all();

        return view('livewire.iso.monitor-page', [
            'rows' => $rows,
            'users' => $users,
            'summary' => $summary,
            'divisionOptions' => $divisionOptions,
            'fromLabel' => Carbon::parse($from)->translatedFormat('j M Y'),
            'toLabel' => Carbon::parse($to)->translatedFormat('j M Y'),
        ])->layout('components.layouts.app', [
            'title' => 'ISO Monitor',
        ]);
    }
}
