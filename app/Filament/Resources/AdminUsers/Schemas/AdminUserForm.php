<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use App\Support\PasswordGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(160)
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                        'max' => __('admin.validation.max'),
                    ]),
                TextInput::make('email')
                    ->label(__('admin.fields.email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                        'email' => __('admin.validation.email'),
                        'unique' => __('admin.validation.unique'),
                    ]),
                TextInput::make('phone')
                    ->label(__('admin.fields.phone'))
                    ->tel()
                    ->maxLength(40)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                    ->validationMessages([
                        'max' => __('admin.validation.max'),
                    ]),
                TextInput::make('telegram')
                    ->label(__('admin.fields.telegram'))
                    ->prefix('@')
                    ->maxLength(64)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                        ? ltrim(trim($state), '@')
                        : null)
                    ->validationMessages([
                        'max' => __('admin.validation.max'),
                    ]),
                TextInput::make('password')
                    ->label(__('admin.fields.password'))
                    ->password()
                    ->revealable()
                    ->helperText(__('admin.fields.password_hint'))
                    ->suffixAction(
                        Action::make('generatePassword')
                            ->label(__('admin.actions.generate_password'))
                            ->icon(Heroicon::ArrowPath)
                            ->tooltip(__('admin.actions.generate_password'))
                            ->action(function (Set $set): void {
                                $password = PasswordGenerator::generate();

                                $set('password', $password);
                                $set('password_confirmation', $password);
                            }),
                    )
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->confirmed()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                        'min' => __('admin.validation.password_min'),
                        'max' => __('admin.validation.max'),
                        'confirmed' => __('admin.validation.password_confirmed'),
                    ]),
                TextInput::make('password_confirmation')
                    ->label(__('admin.fields.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->required(fn (Get $get, string $operation): bool => $operation === 'create' || filled($get('password')))
                    ->dehydrated(false)
                    ->validationMessages(['required' => __('admin.validation.required')]),
            ]);
    }
}
