<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Models\AdminUser;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AdminUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->wrap()->label(__('admin.fields.name')),
                TextColumn::make('email')->searchable()->wrap()->label(__('admin.fields.email')),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (AdminUser $record): string => $record->blocked_at
                        ? __('admin.enums.admin_status.blocked')
                        : __('admin.enums.admin_status.active'))
                    ->color(fn (AdminUser $record): string => $record->blocked_at ? 'danger' : 'success')
                    ->label(__('admin.fields.status')),
                TextColumn::make('created_at')->since()->wrap()->label(__('admin.fields.created_at')),
            ])
            ->filters([
                TernaryFilter::make('blocked_at')
                    ->nullable()
                    ->label(__('admin.fields.status'))
                    ->trueLabel(__('admin.filters.blocked'))
                    ->falseLabel(__('admin.filters.active')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('block')
                    ->label(__('admin.actions.block'))
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->visible(fn (AdminUser $record): bool => $record->blocked_at === null
                        && auth('admin')->id() !== $record->getKey())
                    ->requiresConfirmation()
                    ->action(fn (AdminUser $record) => $record->update(['blocked_at' => now()])),
                Action::make('unblock')
                    ->label(__('admin.actions.unblock'))
                    ->icon(Heroicon::LockOpen)
                    ->visible(fn (AdminUser $record): bool => $record->blocked_at !== null)
                    ->action(fn (AdminUser $record) => $record->update(['blocked_at' => null])),
                DeleteAction::make(),
            ]);
    }
}
