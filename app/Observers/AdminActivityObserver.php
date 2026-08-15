<?php

namespace App\Observers;

use App\Models\AdminUser;
use App\Models\Workspace;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AdminActivityObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'admin.created');
    }

    public function updated(Model $model): void
    {
        $this->log($model, 'admin.updated');
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'admin.deleted');
    }

    private function log(Model $model, string $action): void
    {
        $admin = auth('admin')->user();

        if (! $admin instanceof AdminUser) {
            return;
        }

        $changed = array_keys(Arr::except(
            $model->getChanges(),
            ['updated_at', 'password', 'remember_token', 'token', 'secret'],
        ));

        ActivityLogger::logAdmin(
            $this->workspace($model, $action),
            $action,
            $model,
            [
                'model' => class_basename($model),
                'record_id' => $model->getKey(),
                'name' => $this->label($model),
                'fields' => $changed,
            ],
            $admin,
        );
    }

    private function workspace(Model $model, string $action): ?Workspace
    {
        if ($model instanceof Workspace) {
            return $action === 'admin.deleted' ? null : $model;
        }

        $workspaceId = $model->getAttribute('workspace_id')
            ?? $model->getAttribute('current_workspace_id');

        return $workspaceId ? Workspace::query()->find($workspaceId) : null;
    }

    private function label(Model $model): ?string
    {
        foreach (['name', 'subject', 'label', 'email'] as $attribute) {
            if (filled($value = $model->getAttribute($attribute))) {
                return (string) $value;
            }
        }

        return null;
    }
}
