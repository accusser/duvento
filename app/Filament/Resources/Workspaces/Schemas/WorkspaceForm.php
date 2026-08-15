<?php

namespace App\Filament\Resources\Workspaces\Schemas;

use App\Enums\WorkspacePlan;
use App\Models\Workspace;
use App\Support\UpcomingPayments;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WorkspaceForm
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
                Select::make('plan')
                    ->label(__('admin.fields.plan'))
                    ->options(fn (?Workspace $record) => WorkspacePlan::optionsForEdition($record?->plan))
                    ->placeholder(__('admin.placeholders.select'))
                    ->native(false)
                    ->required()
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                    ]),
                Select::make('currency')
                    ->label(__('admin.fields.currency'))
                    ->options(array_combine(UpcomingPayments::CURRENCIES, UpcomingPayments::CURRENCIES))
                    ->native(false)
                    ->required()
                    ->default('USD')
                    ->validationMessages([
                        'required' => __('admin.validation.required'),
                    ]),
                DateTimePicker::make('blocked_at')->label(__('admin.fields.blocked_at')),
            ]);
    }
}
