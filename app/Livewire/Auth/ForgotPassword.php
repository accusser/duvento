<?php

namespace App\Livewire\Auth;

use App\Support\RateLimits;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function send(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        RateLimits::hitOrFail('password-reset:'.request()->ip());

        Password::sendResetLink(['email' => $this->email]);

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->title(__('app.titles.forgot'));
    }
}
