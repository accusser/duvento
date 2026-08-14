<?php

namespace App\Filament\Resources\WaitlistSignups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WaitlistSignupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->email()->required(),
            TextInput::make('name'),
        ]);
    }
}
