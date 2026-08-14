<?php

namespace App\Filament\Resources\Workspaces\Tables;

use App\Enums\WorkspacePlan;
use App\Models\Workspace;
use App\Support\BillingService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkspacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('plan')->badge(),
                TextColumn::make('clients_count')->counts('clients')->label('Клиенты'),
                TextColumn::make('assets_count')->counts('assets')->label('Активы'),
                TextColumn::make('blocked_at')->dateTime()->placeholder('—')->label('Блок'),
                TextColumn::make('created_at')->since()->label('Создан'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('block')
                    ->label('Блок')
                    ->visible(fn (Workspace $record) => $record->blocked_at === null)
                    ->requiresConfirmation()
                    ->action(fn (Workspace $record) => $record->update(['blocked_at' => now()])),
                Action::make('unblock')
                    ->label('Разблок')
                    ->visible(fn (Workspace $record) => $record->blocked_at !== null)
                    ->action(fn (Workspace $record) => $record->update(['blocked_at' => null])),
                Action::make('grantAgency')
                    ->label('Agency')
                    ->action(fn (Workspace $record) => app(BillingService::class)->activate($record, WorkspacePlan::Agency, 'manual_admin')),
                Action::make('grantStarter')
                    ->label('Starter')
                    ->action(fn (Workspace $record) => app(BillingService::class)->activate($record, WorkspacePlan::Starter, 'manual_admin')),
            ]);
    }
}
