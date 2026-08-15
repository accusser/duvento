<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('workspace_id')
                    ->label(__('admin.fields.workspace'))
                    ->relationship('workspace', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->validationMessages(['required' => __('admin.validation.required')]),
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(160)
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                        'max' => __('admin.validation.max'),
                    ]),
                TextInput::make('contact_name')
                    ->label(__('admin.fields.contact_name'))
                    ->maxLength(160)
                    ->validationMessages(['max' => __('admin.validation.max')]),
                TextInput::make('email')
                    ->label(__('admin.fields.email'))
                    ->email()
                    ->maxLength(255)
                    ->validationMessages([
                        'email' => __('admin.validation.email'),
                        'max' => __('admin.validation.max'),
                    ]),
                TextInput::make('website')
                    ->label(__('admin.fields.website'))
                    ->maxLength(255)
                    ->validationMessages(['max' => __('admin.validation.max')]),
                Textarea::make('notes')
                    ->label(__('admin.fields.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
