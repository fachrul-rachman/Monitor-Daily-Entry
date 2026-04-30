<?php

namespace App\Livewire\Iso;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pengajuan Off')]
class LeaveApprovalPage extends Component
{
    use WithPagination;

    public string $decisionNote = '';

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
        $iso = auth()->user();
        if (! $iso) {
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

        // Pemohon tidak boleh cancel pengajuannya sendiri.
        if ($status === 'cancelled' && (int) $row->user_id === (int) $iso->id) {
            return;
        }

        $note = trim($this->decisionNote);
        $this->decisionNote = '';

        if ($status === 'approved') {
            $row->status = 'approved';
            $row->approved_by = $iso->id;
            $row->approved_at = now();
            $row->rejected_by = null;
            $row->rejected_at = null;
        } elseif ($status === 'rejected') {
            $row->status = 'rejected';
            $row->rejected_by = $iso->id;
            $row->rejected_at = now();
            $row->approved_by = null;
            $row->approved_at = null;
        } elseif ($status === 'cancelled') {
            $row->status = 'cancelled';
            $row->rejected_by = $iso->id;
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

    public function render()
    {
        $pending = LeaveRequest::query()
            ->with(['user:id,name,division_id,role', 'division:id,name'])
            ->where('status', 'pending')
            ->whereIn('user_id', User::query()->whereIn('role', ['hod', 'manager'])->select('id'))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.iso.leave-approval-page', [
            'pending' => $pending,
        ])->layout('components.layouts.app', [
            'title' => 'Pengajuan Off',
        ]);
    }
}
