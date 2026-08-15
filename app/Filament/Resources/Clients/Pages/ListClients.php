<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Concerns\ExportsAdminCsv;
use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    use ExportsAdminCsv;
    use HasAdminSubheading;

    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportCsvAction('duvento-clients', [
                __('admin.fields.name'),
                __('admin.fields.email'),
                __('admin.fields.workspace'),
                __('admin.fields.assets'),
                __('admin.fields.created_at'),
            ], fn ($record) => [
                $record->name,
                $record->email,
                $record->workspace?->name,
                $record->assets_count ?? $record->assets()->count(),
                $record->created_at?->toDateTimeString(),
            ]),
        ];
    }

    protected function eagerLoadExport(Builder $query): void
    {
        $query->with('workspace')->withCount('assets');
    }
}
