<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\ExportsAdminCsv;
use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    use ExportsAdminCsv;
    use HasAdminSubheading;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportCsvAction('duvento-users', [
                __('admin.fields.name'),
                __('admin.fields.email'),
                __('admin.fields.workspaces'),
                __('admin.fields.created_at'),
            ], fn ($record) => [
                $record->name,
                $record->email,
                $record->workspaces->pluck('name')->join(', '),
                $record->created_at?->toDateTimeString(),
            ]),
        ];
    }

    protected function eagerLoadExport(Builder $query): void
    {
        $query->with('workspaces');
    }
}
