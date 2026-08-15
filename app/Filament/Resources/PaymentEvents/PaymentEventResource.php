<?php

namespace App\Filament\Resources\PaymentEvents;

use App\Filament\Concerns\CloudOnlyResource;
use App\Filament\Concerns\HasAdminLexicon;
use App\Filament\Resources\PaymentEvents\Pages\ListPaymentEvents;
use App\Filament\Resources\PaymentEvents\Tables\PaymentEventsTable;
use App\Models\PaymentEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentEventResource extends Resource
{
    use CloudOnlyResource;
    use HasAdminLexicon;

    protected static ?string $model = PaymentEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function adminLexicon(): string
    {
        return 'admin.resources.payments';
    }

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
