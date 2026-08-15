<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    use HasAdminSubheading;

    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
