<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\AdminFilters;
use App\Models\User;
use App\Support\Impersonation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label(__('admin.fields.name')),
                TextColumn::make('email')->searchable()->label(__('admin.fields.email')),
                TextColumn::make('workspaces.name')->badge()->label(__('admin.fields.workspaces')),
                TextColumn::make('created_at')->since()->sortable()->label(__('admin.fields.created_at')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                AdminFilters::userWorkspaces(),
            ])
            ->recordUrl(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                Action::make('impersonate')
                    ->label(__('admin.actions.impersonate'))
                    ->icon('heroicon-o-user-circle')
                    ->action(function ($record) {
                        Impersonation::start($record);

                        return redirect()->route('dashboard');
                    }),
                DeleteAction::make(),
            ]);
    }
}
