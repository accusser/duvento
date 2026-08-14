<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workspace.name')->searchable()->label('Воркспейс'),
                TextColumn::make('plan')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('billing_provider_id')->label('Paddle ID')->placeholder('—'),
                TextColumn::make('trial_ends_at')->date(),
                TextColumn::make('ends_at')->date(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
