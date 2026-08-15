<?php

namespace App\Support;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInviteNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class WorkspaceInviter
{
    public function invite(Workspace $workspace, string $email, WorkspaceRole $role, ?User $inviter = null): WorkspaceInvitation
    {
        $email = strtolower(trim($email));

        if ($workspace->users()->where('users.email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('app.team.already_member'),
            ]);
        }

        $invitation = WorkspaceInvitation::query()
            ->where('workspace_id', $workspace->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->first();

        $payload = [
            'invited_by_id' => $inviter?->id,
            'role' => $role,
            'token' => Str::lower(Str::random(48)),
            'expires_at' => now()->addDays(7),
        ];

        if ($invitation) {
            $invitation->fill($payload)->save();
        } else {
            $invitation = WorkspaceInvitation::query()->create([
                'workspace_id' => $workspace->id,
                'email' => $email,
                ...$payload,
            ]);
        }

        Notification::route('mail', $email)
            ->notify(new WorkspaceInviteNotification($invitation->fresh()));

        return $invitation->fresh();
    }

    public function accept(User $user, string $token): WorkspaceInvitation
    {
        $invitation = WorkspaceInvitation::query()
            ->where('token', $token)
            ->pending()
            ->first();

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'email' => __('app.team.invalid'),
            ]);
        }

        if (strcasecmp($user->email, $invitation->email) !== 0) {
            throw ValidationException::withMessages([
                'email' => __('app.team.email_mismatch'),
            ]);
        }

        if (! $invitation->workspace->users()->whereKey($user->id)->exists()) {
            $invitation->workspace->users()->attach($user->id, ['role' => $invitation->role->value]);
        }

        if ($user->current_workspace_id === null) {
            $user->forceFill(['current_workspace_id' => $invitation->workspace_id])->save();
        }

        $invitation->forceFill(['accepted_at' => now()])->save();

        return $invitation->fresh();
    }

    public function acceptFromSession(User $user): void
    {
        $token = session('invite_token');

        if (! is_string($token) || $token === '') {
            return;
        }

        $this->accept($user, $token);
        session()->forget('invite_token');
    }
}
