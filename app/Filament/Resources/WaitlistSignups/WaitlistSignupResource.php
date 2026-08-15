<?php

namespace App\Filament\Resources\WaitlistSignups;

use App\Filament\Concerns\CloudOnlyResource;
use App\Filament\Concerns\HasAdminLexicon;
use App\Filament\Resources\WaitlistSignups\Pages\CreateWaitlistSignup;
use App\Filament\Resources\WaitlistSignups\Pages\EditWaitlistSignup;
use App\Filament\Resources\WaitlistSignups\Pages\ListWaitlistSignups;
use App\Filament\Resources\WaitlistSignups\Schemas\WaitlistSignupForm;
use App\Filament\Resources\WaitlistSignups\Tables\WaitlistSignupsTable;
use App\Models\WaitlistSignup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WaitlistSignupResource extends Resource
{
    use CloudOnlyResource;
    use HasAdminLexicon;

    protected static ?string $model = WaitlistSignup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function adminLexicon(): string
    {
        return 'admin.resources.waitlist';
    }

    public static function form(Schema $schema): Schema
    {
        return WaitlistSignupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaitlistSignupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWaitlistSignups::route('/'),
            'create' => CreateWaitlistSignup::route('/create'),
            'edit' => EditWaitlistSignup::route('/{record}/edit'),
        ];
    }
}
