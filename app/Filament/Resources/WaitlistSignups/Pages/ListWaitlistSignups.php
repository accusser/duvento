<?php

namespace App\Filament\Resources\WaitlistSignups\Pages;

use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\WaitlistSignups\WaitlistSignupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWaitlistSignups extends ListRecords
{
    use HasAdminSubheading;

    protected static string $resource = WaitlistSignupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
