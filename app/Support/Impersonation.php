<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class Impersonation
{
    public const SESSION_KEY = 'impersonator_admin_id';

    public static function start(User $user): void
    {
        abort_unless(auth('admin')->check(), 403);

        $workspace = $user->currentWorkspace ?? $user->workspaces()->first();

        abort_unless($workspace !== null, 422);
        abort_if($workspace->blocked_at !== null, 403);

        if ($user->current_workspace_id === null) {
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        }

        ActivityLogger::logAdmin($workspace, 'admin.impersonation_started', $user, [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        session([self::SESSION_KEY => auth('admin')->id()]);
        Auth::guard('web')->login($user);
    }

    public static function stop(): void
    {
        $user = auth('web')->user();

        if ($user instanceof User) {
            ActivityLogger::logAdmin($user->currentWorkspace, 'admin.impersonation_stopped', $user, [
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }

        Auth::guard('web')->logout();
        session()->forget(self::SESSION_KEY);
    }

    public static function active(): bool
    {
        return session()->has(self::SESSION_KEY) && auth('web')->check();
    }
}
