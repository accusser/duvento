<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Enums\WorkspacePlan;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workspace.name')->searchable()->label(__('admin.fields.workspace')),
                TextColumn::make('plan')
                    ->badge()
                    ->label(__('admin.fields.plan'))
                    ->formatStateUsing(fn (WorkspacePlan $state) => $state->label()),
                TextColumn::make('status')->badge()->label(__('admin.fields.status')),
                TextColumn::make('billing_provider_id')->label(__('admin.fields.paddle_id'))->placeholder(__('admin.placeholders.empty')),
                TextColumn::make('trial_ends_at')->date()->label(__('admin.fields.trial_ends_at')),
                TextColumn::make('ends_at')->date()->label(__('admin.fields.ends_at')),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
