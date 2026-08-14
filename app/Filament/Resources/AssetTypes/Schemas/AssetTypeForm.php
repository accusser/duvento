<?php

namespace App\Filament\Resources\AssetTypes\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AssetTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')->required()->alphaDash()->maxLength(40),
                TextInput::make('label')->required()->maxLength(80),
                TextInput::make('icon')->default('dot'),
                TagsInput::make('default_reminder_days')->label('Дни напоминаний'),
            ]);
    }
}
