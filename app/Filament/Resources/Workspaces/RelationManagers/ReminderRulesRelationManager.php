<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Enums\ReminderChannel;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReminderRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'workspaceReminderRules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.reminder_rules.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('days_before')
                    ->label(__('admin.fields.reminder_days'))
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Select::make('channel')
                    ->label(__('admin.fields.channel'))
                    ->options([
                        ReminderChannel::Email->value => 'Email',
                    ])
                    ->default(ReminderChannel::Email->value)
                    ->required()
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('days_before')
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('days_before')->label(__('admin.fields.reminder_days')),
                TextColumn::make('channel')->label(__('admin.fields.channel')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
