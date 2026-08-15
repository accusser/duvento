<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
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
                TextInput::make('password')
                    ->label(__('admin.fields.password'))
                    ->password()
                    ->revealable()
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
                Repeater::make('memberships')
                    ->label(__('admin.fields.workspaces'))
                    ->schema([
                        Select::make('workspace_id')
                            ->label(__('admin.fields.workspace'))
                            ->options(fn () => Workspace::query()->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->native(false)
                            ->validationMessages(['required' => __('admin.validation.required')]),
                        Select::make('role')
                            ->label(__('admin.fields.role'))
                            ->options(WorkspaceRole::options())
                            ->default(WorkspaceRole::Member->value)
                            ->required()
                            ->native(false)
                            ->validationMessages(['required' => __('admin.validation.required')]),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel(__('admin.actions.add_workspace'))
                    ->columnSpanFull(),
            ]);
    }
}
