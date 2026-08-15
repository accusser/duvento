<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Concerns\ExportsAdminCsv;
use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdminUsers extends ListRecords
{
    use ExportsAdminCsv;
    use HasAdminSubheading;

    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportCsvAction('duvento-admins', [
                __('admin.fields.name'),
                __('admin.fields.email'),
                __('admin.fields.phone'),
                __('admin.fields.telegram'),
                __('admin.fields.created_at'),
            ], fn ($record) => [
                $record->name,
                $record->email,
                $record->phone,
                $record->telegram,
                $record->created_at?->toDateTimeString(),
            ]),
            CreateAction::make(),
        ];
    }
}
