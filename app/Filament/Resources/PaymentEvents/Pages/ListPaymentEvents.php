<?php

namespace App\Filament\Resources\PaymentEvents\Pages;

use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\PaymentEvents\PaymentEventResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentEvents extends ListRecords
{
    use HasAdminSubheading;

    protected static string $resource = PaymentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
