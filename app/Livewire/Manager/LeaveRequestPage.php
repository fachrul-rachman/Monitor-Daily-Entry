<?php

namespace App\Livewire\Manager;

use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Cuti & Izin')]
class LeaveRequestPage extends Component
{
    use WithPagination;

    public string $type = '';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $reason = '';

    public function mount(): void
    {
        $today = Carbon::today()->toDateString();
        $this->startDate = $this->startDate ?: $today;
        $this->endDate = $this->endDate ?: $today;
    }

    public function submit(): void
    {
        $data = $this->validate([
            'type' => 'required|string|max:50',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $start = Carbon::parse($data['startDate'])->startOfDay();
        $end = Carbon::parse($data['endDate'])->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $user = auth()->user();
        if (! $user) {
            return;
        }

        LeaveRequest::query()->create([
            'user_id' => $user->id,
            'division_id' => $user->division_id,
            'type' => trim($data['type']),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => trim((string) ($data['reason'] ?? '')) ?: null,
            'status' => 'pending',
        ]);

        $this->reset('type', 'reason');
        $today = Carbon::today()->toDateString();
        $this->startDate = $today;
        $this->endDate = $today;

        $this->resetPage();
        $this->dispatch('toast', message: 'Pengajuan cuti/izin berhasil dibuat dan menunggu persetujuan HoD.', type: 'success');
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
            'title' => 'Cuti & Izin',
        ]);
    }
}

