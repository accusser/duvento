<?php

namespace App\Filament\Resources\PaymentEvents;

use App\Filament\Resources\PaymentEvents\Pages\ListPaymentEvents;
use App\Filament\Resources\PaymentEvents\Tables\PaymentEventsTable;
use App\Models\PaymentEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentEventResource extends Resource
{
    protected static ?string $model = PaymentEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Платежи';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return PaymentEventsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentEvents::route('/'),
        ];
    }
}
