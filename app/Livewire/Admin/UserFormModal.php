<?php

namespace App\Livewire\Admin;

use App\Models\Division;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class UserFormModal extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public ?string $division = null;

    public string $password = '';

    public bool $isActive = true;

    public string $discordWebhookUrl = '';

    public bool $show = false;

    public array $availableDivisions = [];

    #[On('open-user-form')]
    public function open(?int $userId = null): void
    {
        $this->resetValidation();
        $this->userId = $userId;
        $this->availableDivisions = Division::orderBy('name')->pluck('name')->all();

        if ($userId) {
            $user = User::findOrFail($userId);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role;
            $this->division = $user->division?->name;
            $this->isActive = $user->status === 'active';
            $this->discordWebhookUrl = (string) ($user->discord_webhook_url ?? '');
            $this->password = '';
        } else {
            $this->name = '';
            $this->email = '';
            $this->role = '';
            $this->division = null;
            $this->isActive = true;
            $this->discordWebhookUrl = '';
            $this->password = '';
        }

        $this->show = true;
    }

    public function rules(): array
    {
        $emailRule = 'required|email|unique:users,email';

        if ($this->userId) {
            $emailRule = 'required|email|unique:users,email,'.$this->userId;
        }

        $passwordRule = $this->userId ? 'nullable|string|min:8' : 'required|string|min:8';

        return [
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'role' => 'required|string|in:admin,director,hod,manager,iso',
            'division' => 'nullable|string',
            'password' => $passwordRule,
            'isActive' => 'boolean',
            'discordWebhookUrl' => 'nullable|string|max:2000',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $divisionId = null;
        if ($this->division) {
            $divisionId = Division::where('name', $this->division)->value('id');
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'division_id' => $divisionId,
            'status' => $this->isActive ? 'active' : 'inactive',
            'discord_webhook_url' => trim($this->discordWebhookUrl) !== '' ? trim($this->discordWebhookUrl) : null,
        ];

        if ($this->password !== '') {
            $data['password'] = $this->password;
        }

        User::updateOrCreate(
            ['id' => $this->userId],
            $data,
        );

        $this->show = false;

        $this->dispatch('user-saved');
    }

    public function render()
    {
        $availableRoles = [
            'admin' => 'Admin',
            'director' => 'Director',
            'hod' => 'HoD',
            'manager' => 'Manager',
            'iso' => 'ISO',
        ];

        return view('livewire.admin.user-form-modal', [
            'availableRoles' => $availableRoles,
            'availableDivisions' => $this->availableDivisions,
            'isEdit' => (bool) $this->userId,
        ]);
    }
}
