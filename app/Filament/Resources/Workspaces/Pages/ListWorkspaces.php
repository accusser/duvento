<?php

namespace App\Filament\Resources\Workspaces\Pages;

use App\Filament\Concerns\ExportsAdminCsv;
use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Workspaces\WorkspaceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkspaces extends ListRecords
{
    use ExportsAdminCsv;
    use HasAdminSubheading;

    protected static string $resource = WorkspaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportCsvAction('duvento-workspaces', [
                __('admin.fields.name'),
                __('admin.fields.plan'),
                __('admin.fields.clients'),
                __('admin.fields.assets'),
                __('admin.fields.block'),
                __('admin.fields.created_at'),
            ], fn ($record) => [
                $record->name,
                $record->plan->label(),
                $record->clients()->count(),
                $record->assets()->count(),
                $record->blocked_at?->toDateTimeString(),
                $record->created_at?->toDateTimeString(),
            ]),
            CreateAction::make(),
        ];
    }
}
