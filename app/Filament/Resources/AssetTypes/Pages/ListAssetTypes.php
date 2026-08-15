<?php

namespace App\Filament\Resources\AssetTypes\Pages;

use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\AssetTypes\AssetTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetTypes extends ListRecords
{
    use HasAdminSubheading;

    protected static string $resource = AssetTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
