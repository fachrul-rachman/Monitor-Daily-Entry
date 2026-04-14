<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\HodAssignment;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Assignment HoD')]
class AssignmentsPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterDivision = '';

    public function openAddModal(?int $assignmentId = null): void
    {
        $this->dispatch('open-assignment-form', assignmentId: $assignmentId);
    }

    public function deleteAssignment(int $assignmentId): void
    {
        $assignment = HodAssignment::find($assignmentId);

        if (! $assignment) {
            return;
        }

        $assignment->delete();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'filterDivision');
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDivision(): void
    {
        $this->resetPage();
    }

    #[On('assignment-saved')]
    public function refreshAssignments(): void
    {
        $this->resetPage();
    }

    public function getDivisionsProperty(): array
    {
        return Division::orderBy('name')->pluck('name')->all();
    }

    public function render()
    {
        $assignments = HodAssignment::query()
            ->with(['hod:id,name,email', 'division:id,name'])
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';

                $query->whereHas('hod', function ($q) use ($term) {
                    $q->where('name', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term);
                })->orWhereHas('division', function ($q) use ($term) {
                    $q->where('name', 'ilike', $term);
                });
            })
            ->when($this->filterDivision, function ($query) {
                $query->whereHas('division', function ($q) {
                    $q->where('name', $this->filterDivision);
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.assignments-page', [
            'assignments' => $assignments,
            'divisions' => $this->divisions,
        ])->layout('components.layouts.app', [
            'title' => 'Assignment HoD',
        ]);
    }
}

