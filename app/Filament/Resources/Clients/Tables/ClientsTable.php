<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Support\AdminFilters;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label(__('admin.fields.name')),
                TextColumn::make('contact_name')->searchable()->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.contact_name')),
                TextColumn::make('email')->searchable()->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.email')),
                TextColumn::make('website')->searchable()->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.website')),
                TextColumn::make('workspace.name')->searchable()->label(__('admin.fields.workspace')),
                TextColumn::make('assets_count')->counts('assets')->label(__('admin.fields.assets')),
                TextColumn::make('created_at')->since()->label(__('admin.fields.created_at')),
            ])
            ->filters([
                AdminFilters::workspace(),
            ])
            ->recordUrl(fn (Client $record): string => ClientResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }
}
