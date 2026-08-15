<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Support\RateLimits;
use App\Support\WorkspaceInviter;
use App\Support\WorkspaceProvisioner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $workspace = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $inviteToken = null;

    public function mount(): void
    {
        $token = request('invite') ?? session('invite_token');

        if (! is_string($token) || $token === '') {
            return;
        }

        $invitation = WorkspaceInvitation::query()->where('token', $token)->pending()->first();

        if ($invitation === null) {
            return;
        }

        $this->inviteToken = $invitation->token;
        $this->email = $invitation->email;
        session(['invite_token' => $invitation->token]);
    }

    public function register(WorkspaceProvisioner $provisioner, WorkspaceInviter $inviter): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'workspace' => [$this->inviteToken ? 'nullable' : 'required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        RateLimits::hitOrFail('register:'.request()->ip());

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if ($this->inviteToken) {
            $user->forceFill(['email' => $this->email])->save();
            Auth::login($user);
            session()->regenerate();
            $inviter->accept($user, $this->inviteToken);
            session()->forget('invite_token');
        } else {
            $provisioner->create($validated['workspace'], $user);
            Auth::login($user);
            session()->regenerate();
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register')->title(__('app.titles.register'));
    }
}
