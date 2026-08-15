<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'assets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.assets.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('name')->searchable()->label(__('admin.fields.name')),
                TextColumn::make('client.name')->label(__('admin.fields.client')),
                TextColumn::make('expires_at')->date()->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.expires_at')),
            ])
            ->defaultSort('expires_at')
            ->headerActions([])
            ->recordActions([
                Action::make('open')
                    ->label(__('admin.actions.open'))
                    ->url(fn (Asset $record): string => AssetResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
