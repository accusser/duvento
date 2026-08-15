<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Support\WorkspaceInviter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class AcceptInvite extends Component
{
    public string $token = '';

    public function mount(string $token, WorkspaceInviter $inviter): void
    {
        $this->token = $token;
        $invitation = WorkspaceInvitation::query()->where('token', $token)->pending()->first();

        if ($invitation === null) {
            session()->flash('status', __('app.team.invalid'));
            $this->redirectRoute('login', navigate: true);

            return;
        }

        session(['invite_token' => $token]);

        if (auth()->check()) {
            $inviter->accept(auth()->user(), $token);
            session()->forget('invite_token');
            session()->flash('status', __('app.flash.invite_accepted'));
            $this->redirectRoute('dashboard', navigate: true);

            return;
        }

        if (User::query()->where('email', $invitation->email)->exists()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $this->redirect(route('register', ['invite' => $token]), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.accept-invite')->title(__('app.titles.invite'));
    }
}
