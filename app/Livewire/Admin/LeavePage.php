<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\LeaveRequest;
use App\Models\HodAssignment;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pengajuan Off')]
class LeavePage extends Component
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
        $this->reset('search', 'status', 'division', 'type');
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

    public function openDetail(int $id): void
    {
        $row = LeaveRequest::query()
            ->with([
                'user:id,name,email,division_id',
                'division:id,name',
                'approver:id,name',
                'rejector:id,name',
            ])
            ->where('id', $id)
            ->first();

        if (! $row) {
            return;
        }

        $this->selectedId = (int) $row->id;
        $this->selected = [
            'id' => (int) $row->id,
            'user' => $row->user?->name ?? '—',
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
        ];

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->selectedId = null;
        $this->selected = [];
    }

    public function approveSelected(): void
    {
        $this->decide('approved');
    }

    public function rejectSelected(): void
    {
        $this->decide('rejected');
    }

    protected function decide(string $decision): void
    {
        if (! $this->selectedId) {
            return;
        }

        $actor = auth()->user();
        if (! $actor || ! in_array($actor->role, ['admin', 'director', 'hod'], true)) {
            return;
        }

        $row = LeaveRequest::query()->where('id', $this->selectedId)->first();
        if (! $row) {
            return;
        }

        if ($decision === 'approved') {
            $row->status = 'approved';
            $row->approved_by = $actor->id;
            $row->approved_at = now();
            $row->rejected_by = null;
            $row->rejected_at = null;
        } else {
            $row->status = 'rejected';
            $row->rejected_by = $actor->id;
            $row->rejected_at = now();
            $row->approved_by = null;
            $row->approved_at = null;
        }

        $row->save();

        $this->dispatch('toast', message: 'Status permintaan berhasil diperbarui.', type: 'success');
        $this->openDetail($row->id);
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

        // Kalau masih satu bulan & tahun, ringkas.
        if ($s->format('Y-m') === $e->format('Y-m')) {
            return $s->format('j').'–'.$e->translatedFormat('j M Y');
        }

        return $s->translatedFormat('j M Y').' – '.$e->translatedFormat('j M Y');
    }

    public function render()
    {
        $actor = auth()->user();
        $scopeDivisionIds = null;

        if ($actor && $actor->role === 'hod') {
            $ids = HodAssignment::query()
                ->where('hod_id', $actor->id)
                ->pluck('division_id')
                ->filter()
                ->values()
                ->map(fn ($v) => (int) $v)
                ->all();

            if (empty($ids) && $actor->division_id) {
                $ids = [(int) $actor->division_id];
            }

            $scopeDivisionIds = array_values(array_unique(array_filter($ids)));
        }

        $from = Carbon::parse($this->from)->toDateString();
        $to = Carbon::parse($this->to)->toDateString();

        $base = LeaveRequest::query()
            ->with(['user:id,name,division_id', 'division:id,name'])
            ->where(function ($q) use ($from, $to) {
                // overlap range
                $q->whereDate('start_date', '<=', $to)
                    ->whereDate('end_date', '>=', $from);
            });

        if (is_array($scopeDivisionIds)) {
            if (empty($scopeDivisionIds)) {
                // HoD tanpa assignment: jangan tampilkan data global.
                $base->whereRaw('1=0');
            } else {
                $base->whereIn('division_id', $scopeDivisionIds);
            }
        }

        if ($this->search !== '') {
            $search = trim($this->search);
            $base->whereHas('user', fn ($q) => $q->where('name', 'ilike', '%'.$search.'%')->orWhere('email', 'ilike', '%'.$search.'%'));
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

        $rows = (clone $base)
            ->orderByRaw("case when status='pending' then 0 when status='approved' then 1 when status='rejected' then 2 else 3 end")
            ->orderByDesc('created_at')
            ->paginate(10);

        $summary = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $divisionOptQuery = Division::query()->orderBy('name');
        if (is_array($scopeDivisionIds)) {
            $divisionOptQuery->whereIn('id', $scopeDivisionIds ?: [-1]);
        }

        $divisionOptions = $divisionOptQuery
            ->get(['id', 'name'])
            ->map(fn ($d) => ['id' => (int) $d->id, 'name' => $d->name])
            ->all();

        $typeOptions = LeaveRequest::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->map(fn ($t) => (string) $t)
            ->values()
            ->all();

        $leaveRequests = $rows->map(function (LeaveRequest $r) {
            return [
                'id' => (int) $r->id,
                'user' => $r->user?->name ?? '—',
                'division' => $r->division?->name ?? ($r->user?->division?->name ?? '—'),
                'type' => $r->type,
                'date' => $this->formatDateRange($r->start_date?->toDateString(), $r->end_date?->toDateString()),
                'reason' => $r->reason ?: '',
                'status' => $r->status,
                'submitted' => $r->created_at ? $r->created_at->translatedFormat('j M Y') : '—',
            ];
        })->all();

        return view('livewire.admin.leave-page', [
            'summaryCount' => $summary,
            'leaveRequests' => $leaveRequests,
            'rows' => $rows,
            'divisionOptions' => $divisionOptions,
            'typeOptions' => $typeOptions,
        ])->layout('components.layouts.app', [
            'title' => 'Pengajuan Off',
        ]);
    }
}
