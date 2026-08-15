<?php

namespace App\Support;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

final class AccountDeleter
{
    public function delete(User $user): void
    {
        $userId = $user->id;

        $user->workspaces()
            ->get()
            ->filter(fn (Workspace $workspace) => $this->isSoleOwner($user, $workspace))
            ->each(fn (Workspace $workspace) => $workspace->delete());

        User::query()->whereKey($userId)->update(['current_workspace_id' => null]);
        User::query()->whereKey($userId)->delete();
    }

    private function isSoleOwner(User $user, Workspace $workspace): bool
    {
        if (! $user->isOwnerOf($workspace)) {
            return false;
        }

        return $workspace->users()
            ->wherePivot('role', WorkspaceRole::Owner->value)
            ->where('users.id', '!=', $user->id)
            ->doesntExist();
    }
}
