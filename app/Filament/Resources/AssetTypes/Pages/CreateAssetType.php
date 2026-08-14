<?php

namespace App\Filament\Resources\AssetTypes\Pages;

use App\Filament\Resources\AssetTypes\AssetTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetType extends CreateRecord
{
    protected static string $resource = AssetTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['workspace_id'] = null;
        $data['default_reminder_days'] = [30, 14, 7, 1];

        return $data;
    }
}
