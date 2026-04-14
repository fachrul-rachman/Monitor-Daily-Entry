<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\HodAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class AssignmentFormModal extends Component
{
    public bool $show = false;

    public ?int $assignmentId = null;

    public ?int $selectedHodId = null;

    /**
     * @var array<int>
     */
    public array $selectedDivisionIds = [];

    /**
     * Cached lists for the select / checklist.
     *
     * @var array<int, array{id:int,name:string,email:string}>
     */
    public array $hodUsers = [];

    /**
     * @var array<int, array{id:int,name:string}>
     */
    public array $divisions = [];

    #[On('open-assignment-form')]
    public function open(?int $assignmentId = null): void
    {
        $this->resetValidation();
        $this->reset(['assignmentId', 'selectedHodId', 'selectedDivisionIds']);

        $this->hodUsers = User::query()
            ->where('role', 'hod')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
            ->all();

        $this->divisions = Division::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Division $d) => ['id' => $d->id, 'name' => $d->name])
            ->all();

        if ($assignmentId) {
            $assignment = HodAssignment::query()->with(['hod', 'division'])->find($assignmentId);

            if ($assignment) {
                $this->assignmentId = $assignment->id;
                $this->selectedHodId = $assignment->hod_id;
                $this->selectedDivisionIds = [$assignment->division_id];
            }
        }

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function save(): void
    {
        $this->validate([
            'selectedHodId' => 'required|integer|exists:users,id',
            'selectedDivisionIds' => 'required|array|min:1',
            'selectedDivisionIds.*' => 'integer|exists:divisions,id',
        ]);

        $hod = User::query()
            ->where('id', $this->selectedHodId)
            ->where('role', 'hod')
            ->first();

        if (! $hod) {
            $this->addError('selectedHodId', 'User yang dipilih bukan HoD.');

            return;
        }

        // Edit mode: hanya 1 divisi agar jelas (1 baris = 1 divisi).
        if ($this->assignmentId && count($this->selectedDivisionIds) !== 1) {
            $this->addError('selectedDivisionIds', 'Untuk edit, pilih tepat 1 divisi.');

            return;
        }

        DB::transaction(function () {
            if ($this->assignmentId) {
                $existing = HodAssignment::find($this->assignmentId);
                $newDivisionId = (int) $this->selectedDivisionIds[0];

                HodAssignment::updateOrCreate(
                    ['division_id' => $newDivisionId],
                    ['hod_id' => (int) $this->selectedHodId],
                );

                if ($existing && $existing->division_id !== $newDivisionId) {
                    $existing->delete();
                }

                return;
            }

            foreach ($this->selectedDivisionIds as $divisionId) {
                HodAssignment::updateOrCreate(
                    ['division_id' => (int) $divisionId],
                    ['hod_id' => (int) $this->selectedHodId],
                );
            }
        });

        $this->show = false;
        $this->dispatch('assignment-saved');
    }

    public function render()
    {
        return view('livewire.admin.assignment-form-modal');
    }
}

