<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Support\AdminFilters;
use App\Models\ActivityLog;
use App\Support\ActivityAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->wrap()->label(__('admin.fields.when')),
                TextColumn::make('workspace.name')->searchable()->wrap()->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.workspace')),
                TextColumn::make('actor')
                    ->state(fn (ActivityLog $record): ?string => $record->actorName())
                    ->placeholder(__('admin.placeholders.system'))
                    ->wrap()
                    ->label(__('admin.fields.who')),
                TextColumn::make('action')
                    ->searchable()
                    ->wrap()
                    ->label(__('admin.fields.action'))
                    ->formatStateUsing(fn (string $state) => ActivityAction::label($state)),
                TextColumn::make('properties')
                    ->label(__('admin.fields.properties'))
                    ->state(fn (ActivityLog $record): string => ActivityAction::teaser(
                        is_array($record->properties) ? $record->properties : null
                    ))
                    ->placeholder(__('admin.placeholders.empty'))
                    ->wrap()
                    ->limit(40)
                    ->tooltip(fn (ActivityLog $record): ?string => ActivityAction::teaser(
                        is_array($record->properties) ? $record->properties : null
                    ) ?: null),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                AdminFilters::workspace(),
                AdminFilters::users(),
                AdminFilters::admins(),
                AdminFilters::activityAction(),
            ])
            ->recordUrl(fn (ActivityLog $record): string => ActivityLogResource::getUrl('view', ['record' => $record]))
            ->recordActions([])
            ->toolbarActions([]);
    }
}
