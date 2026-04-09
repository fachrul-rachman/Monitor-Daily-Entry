<?php

namespace App\Livewire\Shared;

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ChangePasswordPage extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function save(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('toast', message: 'Password berhasil diubah.', type: 'success');
    }

    public function render()
    {
        return view('livewire.shared.change-password-page')
            ->layout('components.layouts.app', [
                'title' => 'Ganti Password',
            ]);
    }
}

