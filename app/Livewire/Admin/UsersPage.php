<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class UsersPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterRole = '';

    #[Url]
    public string $filterDivision = '';

    #[Url]
    public string $filterStatus = '';

    /**
     * Open create / edit modal via frontend event.
     */
    public function openAddModal(?int $userId = null): void
    {
        $this->dispatch('open-user-form', userId: $userId);
    }

    /**
     * Open bulk upload modal via frontend event.
     */
    public function openImportModal(): void
    {
        $this->dispatch('open-user-import');
    }

    public function archiveUser(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $user->status = 'archived';
        $user->save();
    }

    public function deleteUser(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        // Hanya boleh menghapus user yang bukan aktif.
        if ($user->status === 'active') {
            return;
        }

        $user->delete();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'filterRole', 'filterDivision', 'filterStatus');
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDivision(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    #[On('user-saved')]
    public function refreshUsers(): void
    {
        // Trigger re-render + reset ke halaman pertama supaya perubahan langsung terlihat.
        $this->resetPage();
    }

    public function getRolesProperty(): array
    {
        return [
            'admin' => 'Admin',
            'director' => 'Director',
            'hod' => 'HoD',
            'manager' => 'Manager',
        ];
    }

    public function getDivisionsProperty(): array
    {
        return Division::orderBy('name')->pluck('name')->all();
    }

    public function render()
    {
        $users = User::with('division')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term);
                });
            })
            ->when($this->filterRole, fn ($q) => $q->where('role', $this->filterRole))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterDivision, function ($q) {
                $q->whereHas('division', function ($sub) {
                    $sub->where('name', $this->filterDivision);
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.users-page', [
            'users' => $users,
            'roles' => $this->roles,
            'divisions' => $this->divisions,
        ])->layout('components.layouts.app', [
            'title' => 'Users',
        ]);
    }
}
