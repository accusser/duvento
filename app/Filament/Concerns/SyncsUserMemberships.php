<?php

namespace App\Filament\Concerns;

use App\Enums\WorkspaceRole;

trait SyncsUserMemberships
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractMemberships(array $data): array
    {
        $this->memberships = $data['memberships'] ?? [];
        unset($data['memberships']);

        return $data;
    }

    protected function syncMemberships(): void
    {
        $sync = [];

        foreach ($this->memberships ?? [] as $item) {
            $workspaceId = (int) ($item['workspace_id'] ?? 0);

            if ($workspaceId < 1) {
                continue;
            }

            $sync[$workspaceId] = [
                'role' => $item['role'] ?? WorkspaceRole::Member->value,
            ];
        }

        $this->record->workspaces()->sync($sync);

        if ($this->record->current_workspace_id === null || ! isset($sync[$this->record->current_workspace_id])) {
            $this->record->forceFill(['current_workspace_id' => array_key_first($sync)])->save();
        }
    }

    /** @var list<array{workspace_id?: int, role?: string}> */
    protected array $memberships = [];
}
