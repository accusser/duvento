<?php

namespace App\Filament\Resources\PaymentEvents\Tables;

use App\Enums\WorkspacePlan;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->label(__('admin.fields.when')),
                TextColumn::make('workspace.name')->searchable()->label(__('admin.fields.workspace')),
                TextColumn::make('type')->badge()->label(__('admin.fields.type')),
                TextColumn::make('plan')
                    ->badge()
                    ->label(__('admin.fields.plan'))
                    ->formatStateUsing(fn (?WorkspacePlan $state) => $state?->label() ?? __('admin.placeholders.empty')),
                TextColumn::make('amount')->label(__('admin.fields.amount'))->formatStateUsing(fn ($state) => $state ? '$'.number_format($state / 100, 2) : __('admin.placeholders.empty')),
                TextColumn::make('provider_id')->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.paddle_id')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
