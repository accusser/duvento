<?php

namespace App\Livewire\Auth;

use App\Support\WorkspaceInviter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('app.auth.throttled'),
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => __('app.auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        if (session('invite_token')) {
            try {
                app(WorkspaceInviter::class)->acceptFromSession(auth()->user());
            } catch (ValidationException) {
                // email mismatch — stay on dashboard of current workspace
            }
        }

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')->title(__('app.titles.login'));
    }
}
