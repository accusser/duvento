<?php

namespace App\Filament\Resources\Workspaces\Tables;

use App\Enums\WorkspacePlan;
use App\Models\Workspace;
use App\Support\BillingService;
use App\Support\Edition;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WorkspacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label(__('admin.fields.name')),
                TextColumn::make('plan')
                    ->badge()
                    ->label(__('admin.fields.plan'))
                    ->formatStateUsing(fn (WorkspacePlan $state) => $state->label()),
                TextColumn::make('clients_count')->counts('clients')->label(__('admin.fields.clients')),
                TextColumn::make('assets_count')->counts('assets')->label(__('admin.fields.assets')),
                TextColumn::make('blocked_at')->dateTime()->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.block')),
                TextColumn::make('created_at')->since()->label(__('admin.fields.created_at')),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->label(__('admin.fields.plan'))
                    ->options(WorkspacePlan::optionsForEdition()),
                TernaryFilter::make('blocked_at')
                    ->label(__('admin.fields.block'))
                    ->nullable()
                    ->trueLabel(__('admin.filters.blocked'))
                    ->falseLabel(__('admin.filters.active')),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('block')
                    ->label(__('admin.actions.block'))
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->visible(fn (Workspace $record) => $record->blocked_at === null)
                    ->requiresConfirmation()
                    ->action(fn (Workspace $record) => $record->update(['blocked_at' => now()])),
                Action::make('unblock')
                    ->label(__('admin.actions.unblock'))
                    ->icon(Heroicon::LockOpen)
                    ->visible(fn (Workspace $record) => $record->blocked_at !== null)
                    ->action(fn (Workspace $record) => $record->update(['blocked_at' => null])),
                Action::make('grantAgency')
                    ->label(__('admin.actions.grant_agency'))
                    ->icon(Heroicon::BuildingOffice2)
                    ->visible(fn () => Edition::isCloud())
                    ->action(fn (Workspace $record) => app(BillingService::class)->activate($record, WorkspacePlan::Agency, 'manual_admin')),
                Action::make('grantStarter')
                    ->label(__('admin.actions.grant_starter'))
                    ->icon(Heroicon::Star)
                    ->visible(fn () => Edition::isCloud())
                    ->action(fn (Workspace $record) => app(BillingService::class)->activate($record, WorkspacePlan::Starter, 'manual_admin')),
            ]);
    }
}
