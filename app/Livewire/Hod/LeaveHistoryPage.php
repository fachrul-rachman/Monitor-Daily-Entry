<?php

namespace App\Livewire\Hod;

use App\Models\Division;
use App\Models\HodAssignment;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('History Off')]
class LeaveHistoryPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $division = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $decidedBy = '';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public bool $drawerOpen = false;
    public ?int $selectedId = null;

    /** @var array<string, mixed> */
    public array $selected = [];

    public function mount(): void
    {
        $today = Carbon::today();
        $this->from = $this->from ?: $today->copy()->subDays(30)->toDateString();
        $this->to = $this->to ?: $today->toDateString();
        $this->normalizeDates();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingDivision(): void { $this->resetPage(); }
    public function updatingType(): void { $this->resetPage(); }
    public function updatingDecidedBy(): void { $this->resetPage(); }
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
        $this->reset('search', 'status', 'division', 'type', 'decidedBy');
        $this->from = $today->copy()->subDays(30)->toDateString();
        $this->to = $today->toDateString();
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
            $from = $today->copy()->subDays(30);
            $to = $today;
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($to->diffInDays($from) > 180) {
            $from = $to->copy()->subDays(180);
        }

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    /**
     * @return array<int>
     */
    protected function assignedDivisionIds(int $hodId, $fallbackDivisionId): array
    {
        $ids = HodAssignment::query()
            ->where('hod_id', $hodId)
            ->pluck('division_id')
            ->filter()
            ->values()
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($ids) && $fallbackDivisionId) {
            $ids = [(int) $fallbackDivisionId];
        }

        return array_values(array_unique(array_filter($ids)));
    }

    protected function formatDateRange(?string $start, ?string $end): string
    {
        if (! $start || ! $end) {
            return '—';
        }

        try {
            $s = Carbon::parse($start);
            $e = Carbon::parse($end);
        } catch (\Throwable) {
            return '—';
        }

        if ($s->equalTo($e)) {
            return $s->translatedFormat('j M Y');
        }

        if ($s->format('Y-m') === $e->format('Y-m')) {
            return $s->format('j').'–'.$e->translatedFormat('j M Y');
        }

        return $s->translatedFormat('j M Y').' – '.$e->translatedFormat('j M Y');
    }

    public function openDetail(int $id): void
    {
        $hod = auth()->user();
        if (! $hod) {
            return;
        }

        $allowedDivisionIds = $this->assignedDivisionIds((int) $hod->id, $hod->division_id);

        $row = LeaveRequest::query()
            ->with([
                'user:id,name,email,division_id,role',
                'division:id,name',
                'approver:id,name,role',
                'rejector:id,name,role',
            ])
            ->where('id', $id)
            ->first();

        if (! $row) {
            return;
        }

        $inScope = $row->division_id !== null && in_array((int) $row->division_id, $allowedDivisionIds, true);
        $isOwn = (int) $row->user_id === (int) $hod->id;
        if (! $inScope && ! $isOwn) {
            return;
        }

        $this->selectedId = (int) $row->id;
        $this->selected = [
            'id' => (int) $row->id,
            'user' => $row->user?->name ?? '—',
            'email' => $row->user?->email ?? '—',
            'role' => $row->user?->role ?? '—',
            'division' => $row->division?->name ?? ($row->user?->division?->name ?? '—'),
            'type' => $row->type,
            'date' => $this->formatDateRange($row->start_date?->toDateString(), $row->end_date?->toDateString()),
            'reason' => $row->reason ?: '—',
            'status' => $row->status,
            'submitted' => $row->created_at ? $row->created_at->translatedFormat('j M Y, H:i') : '—',
            'approved_by' => $row->approver?->name,
            'approved_at' => $row->approved_at?->translatedFormat('j M Y, H:i'),
            'rejected_by' => $row->rejector?->name,
            'rejected_at' => $row->rejected_at?->translatedFormat('j M Y, H:i'),
            'decision_note' => $row->decision_note ?: '',
            'attachment' => [
                'path' => $row->attachment_path,
                'name' => $row->attachment_original_name,
                'size' => $row->attachment_size_bytes,
            ],
        ];

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->selectedId = null;
        $this->selected = [];
    }

    public function render()
    {
        $hod = auth()->user();
        if (! $hod) {
            abort(403);
        }

        $allowedDivisionIds = $this->assignedDivisionIds((int) $hod->id, $hod->division_id);
        if (empty($allowedDivisionIds)) {
            $allowedDivisionIds = [-1];
        }

        $from = Carbon::parse($this->from)->toDateString();
        $to = Carbon::parse($this->to)->toDateString();

        $base = LeaveRequest::query()
            ->with([
                'user:id,name,email,division_id,role',
                'division:id,name',
                'approver:id,name',
                'rejector:id,name',
            ])
            ->where(function ($q) use ($from, $to) {
                $q->whereDate('start_date', '<=', $to)
                    ->whereDate('end_date', '>=', $from);
            })
            ->where(function ($q) use ($hod, $allowedDivisionIds) {
                $q->whereIn('division_id', $allowedDivisionIds)
                    ->orWhere('user_id', (int) $hod->id);
            })
            ->whereIn('user_id', User::query()->whereIn('role', ['hod', 'manager'])->select('id'));

        if (trim($this->search) !== '') {
            $s = trim($this->search);
            $base->whereHas('user', fn ($q) => $q->where('name', 'ilike', '%'.$s.'%')->orWhere('email', 'ilike', '%'.$s.'%'));
        }

        if ($this->status !== '') {
            $base->where('status', $this->status);
        }

        if ($this->division !== '') {
            $base->where('division_id', (int) $this->division);
        }

        if ($this->type !== '') {
            $base->where('type', $this->type);
        }

        if ($this->decidedBy !== '') {
            $deciderId = (int) $this->decidedBy;
            $base->where(function ($q) use ($deciderId) {
                $q->where('approved_by', $deciderId)->orWhere('rejected_by', $deciderId);
            });
        }

        $rows = (clone $base)->orderByDesc('created_at')->paginate(10);

        $summary = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
        ];

        $divisionOptions = Division::query()
            ->whereIn('id', $allowedDivisionIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Division $d) => ['id' => (int) $d->id, 'name' => (string) $d->name])
            ->all();

        $typeOptions = LeaveRequest::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->map(fn ($t) => (string) $t)
            ->values()
            ->all();

        $deciderIds = (clone $base)
            ->where(function ($q) {
                $q->whereNotNull('approved_by')->orWhereNotNull('rejected_by');
            })
            ->get(['approved_by', 'rejected_by'])
            ->flatMap(function ($r) {
                return array_filter([(int) $r->approved_by, (int) $r->rejected_by]);
            })
            ->unique()
            ->values()
            ->all();

        $deciderOptions = empty($deciderIds)
            ? []
            : User::query()
                ->whereIn('id', $deciderIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $u) => ['id' => (int) $u->id, 'name' => (string) $u->name])
                ->all();

        $leaveRequests = $rows->map(function (LeaveRequest $r) {
            $decidedBy = $r->approver?->name ?: ($r->rejector?->name ?: '');
            $decidedAt = $r->approved_at ?: $r->rejected_at;

            return [
                'id' => (int) $r->id,
                'user' => $r->user?->name ?? '—',
                'division' => $r->division?->name ?? ($r->user?->division?->name ?? '—'),
                'type' => (string) $r->type,
                'date' => $this->formatDateRange($r->start_date?->toDateString(), $r->end_date?->toDateString()),
                'status' => (string) $r->status,
                'submitted' => $r->created_at ? $r->created_at->translatedFormat('j M Y') : '—',
                'decided_by' => $decidedBy !== '' ? $decidedBy : '—',
                'decided_at' => $decidedAt ? $decidedAt->translatedFormat('j M Y') : '—',
            ];
        })->all();

        return view('livewire.hod.leave-history-page', [
            'summaryCount' => $summary,
            'leaveRequests' => $leaveRequests,
            'rows' => $rows,
            'divisionOptions' => $divisionOptions,
            'typeOptions' => $typeOptions,
            'deciderOptions' => $deciderOptions,
        ])->layout('components.layouts.app', [
            'title' => 'History Off',
        ]);
    }
}

