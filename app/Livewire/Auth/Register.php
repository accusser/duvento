<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\WorkspaceProvisioner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Регистрация — Duvento')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $workspace = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(WorkspaceProvisioner $provisioner): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'workspace' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $provisioner->create($validated['workspace'], $user);

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
