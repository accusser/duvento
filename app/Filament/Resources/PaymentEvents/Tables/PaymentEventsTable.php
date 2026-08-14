<?php

namespace App\Filament\Resources\PaymentEvents\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->label('Когда'),
                TextColumn::make('workspace.name')->searchable()->label('Воркспейс'),
                TextColumn::make('type')->badge()->label('Тип'),
                TextColumn::make('plan')->badge(),
                TextColumn::make('amount')->label('Сумма')->formatStateUsing(fn ($state) => $state ? '$'.number_format($state / 100, 2) : '—'),
                TextColumn::make('provider_id')->placeholder('—')->label('Paddle ID'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
