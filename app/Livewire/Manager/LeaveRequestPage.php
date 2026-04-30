<?php

namespace App\Livewire\Manager;

use App\Models\LeaveRequest;
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

    public string $type = '';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $reason = '';
    public $attachment;

    public function mount(): void
    {
        $today = Carbon::today()->toDateString();
        $this->startDate = $this->startDate ?: $today;
        $this->endDate = $this->endDate ?: $today;
    }

    public function submit(): void
    {
        $data = $this->validate([
            'type' => 'required|string|in:cuti,izin,skip',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'reason' => 'nullable|string',
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

        $user = auth()->user();
        if (! $user) {
            return;
        }

        $file = $this->attachment;
        $path = $file->store('leave-attachments', 'public');

        LeaveRequest::query()->create([
            'user_id' => $user->id,
            'division_id' => $user->division_id,
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

        $this->resetPage();
        $this->dispatch('toast', message: 'Pengajuan off berhasil dibuat dan menunggu persetujuan.', type: 'success');
    }

    public function render()
    {
        $user = auth()->user();

        $rows = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->orderByRaw("case when status='pending' then 0 when status='approved' then 1 when status='rejected' then 2 when status='cancelled' then 3 else 4 end")
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.manager.leave-request-page', [
            'rows' => $rows,
        ])->layout('components.layouts.app', [
            'title' => 'Pengajuan Off',
        ]);
    }
}
