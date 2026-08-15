<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClientsRelationManager extends RelationManager
{
    protected static string $relationship = 'clients';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.clients.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(160),
                TextInput::make('email')
                    ->label(__('admin.fields.email'))
                    ->email()
                    ->maxLength(255),
            ]);
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
                TextColumn::make('email')->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.email')),
                TextColumn::make('assets_count')->counts('assets')->label(__('admin.fields.assets')),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('open')
                    ->label(__('admin.actions.open'))
                    ->url(fn (Client $record): string => ClientResource::getUrl('view', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }
}
