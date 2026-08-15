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
                TextInput::make('key')
                    ->required()
                    ->alphaDash()
                    ->maxLength(40)
                    ->label(__('admin.fields.key'))
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                        'alpha_dash' => __('admin.validation.alpha_dash'),
                        'max' => __('admin.validation.max'),
                    ]),
                TextInput::make('label')
                    ->required()
                    ->maxLength(80)
                    ->label(__('admin.fields.label'))
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                        'max' => __('admin.validation.max'),
                    ]),
                TextInput::make('icon')->default('dot')->label(__('admin.fields.icon')),
                TagsInput::make('default_reminder_days')->label(__('admin.fields.reminder_days')),
            ]);
    }
}
