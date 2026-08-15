<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Support\WorkspaceInviter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InvitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'invitations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.invitations.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action
                ->iconButton()
                ->tooltip($action->getTooltip() ?? $action->getLabel()))
            ->columns([
                TextColumn::make('email')->searchable()->label(__('admin.fields.email')),
                TextColumn::make('role')
                    ->label(__('admin.fields.role'))
                    ->formatStateUsing(fn (WorkspaceRole $state) => $state->label()),
                TextColumn::make('expires_at')->dateTime()->label(__('admin.fields.expires_at')),
                TextColumn::make('accepted_at')->dateTime()->placeholder(__('admin.placeholders.empty'))->label(__('admin.fields.accepted_at')),
            ])
            ->headerActions([
                Action::make('invite')
                    ->label(__('admin.actions.invite'))
                    ->schema([
                        TextInput::make('email')->label(__('admin.fields.email'))->email()->required(),
                        Select::make('role')
                            ->label(__('admin.fields.role'))
                            ->options(WorkspaceRole::options())
                            ->default(WorkspaceRole::Member->value)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (array $data): void {
                        /** @var Workspace $workspace */
                        $workspace = $this->getOwnerRecord();
                        app(WorkspaceInviter::class)->invite(
                            $workspace,
                            $data['email'],
                            WorkspaceRole::from($data['role']),
                        );
                        Notification::make()->title(__('app.flash.invite_sent'))->success()->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (WorkspaceInvitation $record): bool => $record->accepted_at === null),
            ]);
    }
}
