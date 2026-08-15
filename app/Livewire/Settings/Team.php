<?php

namespace App\Livewire\Settings;

use App\Enums\WorkspaceRole;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\WorkspaceInviter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Team extends Component
{
    use InteractsWithWorkspace;

    public string $email = '';

    public string $role = 'member';

    public function mount(): void
    {
        $this->assertOwner();
    }

    public function invite(WorkspaceInviter $inviter): void
    {
        $this->assertOwner();
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:owner,member'],
        ]);

        $inviter->invite($this->workspace(), $this->email, WorkspaceRole::from($this->role), auth()->user());
        $this->reset('email');
        $this->role = WorkspaceRole::Member->value;
        $this->toast(__('app.flash.invite_sent'));
    }

    public function changeRole(int $userId, string $role): void
    {
        $this->assertOwner();
        abort_unless(in_array($role, [WorkspaceRole::Owner->value, WorkspaceRole::Member->value], true), 422);

        $workspace = $this->workspace();
        $user = $workspace->users()->whereKey($userId)->firstOrFail();

        if ($role === WorkspaceRole::Member->value && $workspace->isLastOwner($user)) {
            $this->addError('role', __('app.team.last_owner'));

            return;
        }

        $workspace->users()->updateExistingPivot($userId, ['role' => $role]);
        $this->toast(__('app.flash.member_role'));
    }

    public function remove(int $userId): void
    {
        $this->assertOwner();
        $workspace = $this->workspace();
        $user = $workspace->users()->whereKey($userId)->firstOrFail();

        if ($workspace->isLastOwner($user)) {
            $this->addError('role', __('app.team.last_owner'));

            return;
        }

        $workspace->users()->detach($userId);

        if ($user->current_workspace_id === $workspace->id) {
            $next = $user->workspaces()->first();
            $user->forceFill(['current_workspace_id' => $next?->id])->save();
        }

        $this->toast(__('app.flash.member_removed'));
    }

    public function revoke(int $invitationId): void
    {
        $this->assertOwner();
        $this->workspace()->invitations()->whereKey($invitationId)->whereNull('accepted_at')->delete();
        $this->toast(__('app.flash.invite_revoked'));
    }

    public function render()
    {
        $workspace = $this->workspace();

        return view('livewire.settings.team', [
            'members' => $workspace->users()->orderBy('name')->get(),
            'invites' => $workspace->invitations()->pending()->latest()->get(),
            'roles' => WorkspaceRole::options(),
        ])->title(__('app.titles.team'));
    }
}
