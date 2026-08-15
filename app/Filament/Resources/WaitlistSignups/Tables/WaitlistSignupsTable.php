<?php

namespace App\Filament\Resources\WaitlistSignups\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WaitlistSignupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('email')->searchable()->label(__('admin.fields.email')),
                TextColumn::make('name')->searchable()->label(__('admin.fields.name')),
                TextColumn::make('created_at')->since()->label(__('admin.fields.when')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
