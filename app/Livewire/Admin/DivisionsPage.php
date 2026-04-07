<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Divisions')]
class DivisionsPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function openCreate(): void
    {
        $this->dispatch('open-division-form', divisionId: null);
    }

    public function openEdit(int $divisionId): void
    {
        $this->dispatch('open-division-form', divisionId: $divisionId);
    }

    public function archive(int $divisionId): void
    {
        $division = Division::find($divisionId);

        if (! $division) {
            return;
        }

        $division->status = 'archived';
        $division->save();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('division-saved')]
    public function refreshDivisions(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $divisions = Division::withCount('users')
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where('name', 'ilike', $term);
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.divisions-page', [
            'divisions' => $divisions,
        ])->layout('components.layouts.app', [
            'title' => 'Divisi',
        ]);
    }
}
