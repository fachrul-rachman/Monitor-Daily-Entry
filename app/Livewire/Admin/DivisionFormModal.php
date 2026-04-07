<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use Livewire\Attributes\On;
use Livewire\Component;

class DivisionFormModal extends Component
{
    public ?int $divisionId = null;

    public string $name = '';

    public bool $isActive = true;

    public bool $show = false;

    #[On('open-division-form')]
    public function open(?int $divisionId = null): void
    {
        $this->resetValidation();
        $this->divisionId = $divisionId;

        if ($divisionId) {
            $division = Division::findOrFail($divisionId);
            $this->name = $division->name;
            $this->isActive = $division->status === 'active';
        } else {
            $this->name = '';
            $this->isActive = true;
        }

        $this->show = true;
    }

    public function rules(): array
    {
        $nameRule = 'required|string|max:255|unique:divisions,name';

        if ($this->divisionId) {
            $nameRule = 'required|string|max:255|unique:divisions,name,'.$this->divisionId;
        }

        return [
            'name' => $nameRule,
            'isActive' => 'boolean',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Division::updateOrCreate(
            ['id' => $this->divisionId],
            [
                'name' => $this->name,
                'status' => $this->isActive ? 'active' : 'inactive',
            ],
        );

        $this->show = false;

        $this->dispatch('division-saved');
    }

    public function render()
    {
        return view('livewire.admin.division-form-modal', [
            'isEdit' => (bool) $this->divisionId,
        ]);
    }
}

