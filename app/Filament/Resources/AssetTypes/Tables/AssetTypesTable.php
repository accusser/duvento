<?php

namespace App\Filament\Resources\AssetTypes\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('key')->searchable()->label(__('admin.fields.key')),
                TextColumn::make('label')
                    ->searchable()
                    ->label(__('admin.fields.label'))
                    ->formatStateUsing(fn ($state, $record) => $record->displayLabel()),
                TextColumn::make('icon')->label(__('admin.fields.icon')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
