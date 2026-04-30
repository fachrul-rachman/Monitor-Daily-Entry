<?php

namespace App\Livewire\Hod;

use App\Models\Division;
use App\Models\HodAssignment;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Pengajuan Off')]
class LeaveRequestPage extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $tab = 'approve'; // approve | mine

    public string $type = '';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $reason = '';
    public string $divisionId = '';
    public $attachment;

    public string $decisionNote = '';

    public function mount(): void
    {
        $today = Carbon::today()->toDateString();
        $this->startDate = $this->startDate ?: $today;
        $this->endDate = $this->endDate ?: $today;

        $divisions = $this->divisionOptions();
        if ($this->divisionId === '' && ! empty($divisions)) {
            $this->divisionId = (string) $divisions[0]['id'];
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['approve', 'mine'], true)) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function submit(): void
    {
        $data = $this->validate([
            'type' => 'required|string|in:cuti,izin,skip',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'reason' => 'nullable|string',
            'divisionId' => 'required|integer',
            'attachment' => 'required|file|max:51200',
        ]);

        $start = Carbon::parse($data['startDate'])->startOfDay();
        $end = Carbon::parse($data['endDate'])->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($data['type'] === 'skip') {
            $end = $start->copy();
        }

        if ($data['type'] === 'izin' && $start->eq($end)) {
            $this->addError('endDate', 'Izin untuk penugasan lebih dari 1 hari.');
            return;
        }

        $hod = auth()->user();
        if (! $hod) {
            return;
        }

        $allowedDivisionIds = $this->assignedDivisionIds($hod->id, $hod->division_id);
        if (! in_array((int) $data['divisionId'], $allowedDivisionIds, true)) {
            return;
        }

        $file = $this->attachment;
        $path = $file->store('leave-attachments', 'public');

        LeaveRequest::query()->create([
            'user_id' => $hod->id,
            'division_id' => (int) $data['divisionId'],
            'type' => trim($data['type']),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => trim((string) ($data['reason'] ?? '')) ?: null,
            'attachment_path' => $path,
            'attachment_original_name' => $file->getClientOriginalName(),
            'attachment_mime_type' => $file->getMimeType(),
            'attachment_size_bytes' => $file->getSize(),
            'status' => 'pending',
        ]);

        $this->reset('type', 'reason', 'attachment');
        $today = Carbon::today()->toDateString();
        $this->startDate = $today;
        $this->endDate = $today;

        $this->tab = 'mine';
        $this->resetPage();
        $this->dispatch('toast', message: 'Pengajuan off berhasil dibuat dan menunggu persetujuan.', type: 'success');
    }

    public function approve(int $id): void
    {
        $this->decide($id, 'approved');
    }

    public function reject(int $id): void
    {
        $this->decide($id, 'rejected');
    }

    public function cancel(int $id): void
    {
        $this->decide($id, 'cancelled');
    }

    protected function decide(int $id, string $status): void
    {
        $hod = auth()->user();
        if (! $hod) {
            return;
        }

        $allowedDivisionIds = $this->assignedDivisionIds($hod->id, $hod->division_id);
        if (empty($allowedDivisionIds)) {
            return;
        }

        /** @var LeaveRequest|null $row */
        $row = LeaveRequest::query()
            ->with(['user:id,role'])
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (! $row) {
            return;
        }

        // HoD hanya boleh memutuskan permintaan Manager dalam scope divisinya.
        $isManagerRequest = ($row->user?->role ?? null) === 'manager';
        if (! $isManagerRequest) {
            return;
        }

        if ($row->division_id === null || ! in_array((int) $row->division_id, $allowedDivisionIds, true)) {
            return;
        }

        // Pemohon tidak boleh cancel pengajuannya sendiri.
        if ($status === 'cancelled' && (int) $row->user_id === (int) $hod->id) {
            return;
        }

        $note = trim($this->decisionNote);
        $this->decisionNote = '';

        if ($status === 'approved') {
            $row->status = 'approved';
            $row->approved_by = $hod->id;
            $row->approved_at = now();
            $row->rejected_by = null;
            $row->rejected_at = null;
        } elseif ($status === 'rejected') {
            $row->status = 'rejected';
            $row->rejected_by = $hod->id;
            $row->rejected_at = now();
            $row->approved_by = null;
            $row->approved_at = null;
        } elseif ($status === 'cancelled') {
            $row->status = 'cancelled';
            $row->rejected_by = $hod->id;
            $row->rejected_at = now();
            $row->approved_by = null;
            $row->approved_at = null;
        } else {
            return;
        }

        $row->decision_note = $note !== '' ? $note : null;
        $row->save();

        $this->dispatch('toast', message: 'Status permintaan berhasil diperbarui.', type: 'success');
        $this->resetPage();
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

    /**
     * @return array<int, array{id:int,name:string}>
     */
    protected function divisionOptions(): array
    {
        $hod = auth()->user();
        if (! $hod) {
            return [];
        }

        $ids = $this->assignedDivisionIds($hod->id, $hod->division_id);
        if (empty($ids)) {
            return [];
        }

        return Division::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Division $d) => ['id' => (int) $d->id, 'name' => (string) $d->name])
            ->all();
    }

    public function render()
    {
        $hod = auth()->user();
        $allowedDivisionIds = $hod ? $this->assignedDivisionIds($hod->id, $hod->division_id) : [];

        $pending = LeaveRequest::query()
            ->with(['user:id,name,division_id,role', 'division:id,name'])
            ->where('status', 'pending')
            ->whereIn('division_id', $allowedDivisionIds ?: [-1])
            ->whereIn('user_id', User::query()->where('role', 'manager')->select('id'))
            ->orderByDesc('created_at')
            ->paginate(10, pageName: 'pendingPage');

        $mine = LeaveRequest::query()
            ->where('user_id', $hod->id)
            ->orderByRaw("case when status='pending' then 0 when status='approved' then 1 when status='rejected' then 2 when status='cancelled' then 3 else 4 end")
            ->orderByDesc('created_at')
            ->paginate(10, pageName: 'minePage');

        return view('livewire.hod.leave-request-page', [
            'tab' => $this->tab,
            'divisionOptions' => $this->divisionOptions(),
            'pending' => $pending,
            'mine' => $mine,
        ])->layout('components.layouts.app', [
            'title' => 'Pengajuan Off',
        ]);
    }
}
