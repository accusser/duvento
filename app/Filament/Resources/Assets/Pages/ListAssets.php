<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Concerns\ExportsAdminCsv;
use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Assets\AssetResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAssets extends ListRecords
{
    use ExportsAdminCsv;
    use HasAdminSubheading;

    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportCsvAction('duvento-assets', [
                __('admin.fields.name'),
                __('admin.fields.workspace'),
                __('admin.fields.client'),
                __('admin.fields.type'),
                __('admin.fields.expires_at'),
                __('admin.fields.status'),
            ], fn ($record) => [
                $record->name,
                $record->workspace?->name,
                $record->client?->name,
                $record->assetType?->displayLabel(),
                $record->expires_at?->toDateString(),
                $record->status->label(),
            ]),
        ];
    }

    protected function eagerLoadExport(Builder $query): void
    {
        $query->with(['workspace', 'client', 'assetType']);
    }
}
