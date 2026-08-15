<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Support\AdminFilters;
use App\Models\Asset;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label(__('admin.fields.name')),
                TextColumn::make('workspace.name')->searchable()->label(__('admin.fields.workspace')),
                TextColumn::make('client.name')->searchable()->label(__('admin.fields.client')),
                TextColumn::make('assetType.label')
                    ->label(__('admin.fields.type'))
                    ->formatStateUsing(fn ($state, $record) => $record->assetType?->displayLabel() ?? $state),
                TextColumn::make('expires_at')->date()->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.expires_at')),
                TextColumn::make('owner')->badge()->label(__('admin.fields.owner')),
                TextColumn::make('created_at')->since()->label(__('admin.fields.created_at')),
            ])
            ->defaultSort('expires_at')
            ->filters([
                AdminFilters::workspace(),
                AdminFilters::assetType(),
                AdminFilters::assetStatus(),
            ])
            ->recordUrl(fn (Asset $record): string => AssetResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }
}
