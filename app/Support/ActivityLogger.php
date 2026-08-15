<?php

namespace App\Support;

use App\Events\WorkspaceActivityOccurred;
use App\Models\ActivityLog;
use App\Models\AdminUser;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

final class ActivityLogger
{
    public static function log(
        Workspace $workspace,
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
    ): ActivityLog {
        $log = ActivityLog::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
        ]);

        event(new WorkspaceActivityOccurred($log));

        return $log;
    }

    public static function logAdmin(
        ?Workspace $workspace,
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?AdminUser $admin = null,
    ): ActivityLog {
        $actor = $admin ?? auth('admin')->user();

        if (! $actor instanceof AdminUser) {
            $actor = AdminUser::query()->find(session(Impersonation::SESSION_KEY));
        }

        return ActivityLog::query()->create([
            'workspace_id' => $workspace?->id,
            'admin_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
        ]);
    }
}
