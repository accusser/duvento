<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\AssetType;
use App\Models\Client;
use App\Support\UpcomingPayments;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AssetForm
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
                    ->live()
                    ->native(false)
                    ->required()
                    ->validationMessages(['required' => __('admin.validation.required')]),
                Select::make('client_id')
                    ->label(__('admin.fields.client'))
                    ->options(fn (Get $get) => Client::query()
                        ->when($get('workspace_id'), fn ($q, $id) => $q->where('workspace_id', $id))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->validationMessages(['required' => __('admin.validation.required')]),
                Select::make('asset_type_id')
                    ->label(__('admin.fields.type'))
                    ->options(fn (Get $get) => AssetType::query()
                        ->availableFor((int) ($get('workspace_id') ?: 0))
                        ->get()
                        ->sortBy(fn (AssetType $type) => mb_strtolower($type->displayLabel()))
                        ->mapWithKeys(fn (AssetType $type) => [$type->id => $type->displayLabel()]))
                    ->native(false)
                    ->required()
                    ->validationMessages(['required' => __('admin.validation.required')]),
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                        'max' => __('admin.validation.max'),
                    ]),
                DatePicker::make('expires_at')->label(__('admin.fields.expires_at')),
                Select::make('owner')
                    ->label(__('admin.fields.owner'))
                    ->options(__('admin.enums.party'))
                    ->native(false)
                    ->required()
                    ->default('unknown')
                    ->validationMessages(['required' => __('admin.validation.required')]),
                Select::make('payer')
                    ->label(__('admin.fields.payer'))
                    ->options(__('admin.enums.party'))
                    ->native(false)
                    ->required()
                    ->default('unknown')
                    ->validationMessages(['required' => __('admin.validation.required')]),
                Select::make('auto_renew')
                    ->label(__('admin.fields.auto_renew'))
                    ->options(__('admin.enums.auto_renew'))
                    ->native(false)
                    ->required()
                    ->default('unknown')
                    ->validationMessages(['required' => __('admin.validation.required')]),
                TextInput::make('notice_email')
                    ->label(__('admin.fields.notice_email'))
                    ->email()
                    ->validationMessages(['email' => __('admin.validation.email')]),
                Toggle::make('ssl_check_enabled')->label(__('admin.fields.ssl_check')),
                TextInput::make('renewal_cost')
                    ->label(__('admin.fields.renewal_cost'))
                    ->numeric()
                    ->minValue(0),
                Select::make('currency')
                    ->label(__('admin.fields.currency'))
                    ->options(array_combine(UpcomingPayments::CURRENCIES, UpcomingPayments::CURRENCIES))
                    ->native(false)
                    ->nullable(),
                Textarea::make('notes')
                    ->label(__('admin.fields.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
