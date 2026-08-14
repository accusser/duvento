<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->label('Когда'),
                TextColumn::make('workspace.name')->searchable()->label('Воркспейс'),
                TextColumn::make('user.name')->placeholder('система')->label('Кто'),
                TextColumn::make('action')->searchable()->label('Действие'),
                TextColumn::make('properties')->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_UNESCAPED_UNICODE) : $state),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
