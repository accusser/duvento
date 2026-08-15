<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Enums\WorkspaceRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Impersonation;
use App\Support\WorkspaceInviter;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.users.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('role')
                    ->label(__('admin.fields.role'))
                    ->options(WorkspaceRole::options())
                    ->required()
                    ->native(false),
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
                TextColumn::make('email')->searchable()->label(__('admin.fields.email')),
                TextColumn::make('role')
                    ->label(__('admin.fields.role'))
                    ->formatStateUsing(fn ($state) => WorkspaceRole::tryFrom((string) $state)?->label() ?? $state),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->label(__('admin.fields.role'))
                            ->options(WorkspaceRole::options())
                            ->default(WorkspaceRole::Member->value)
                            ->required()
                            ->native(false),
                    ]),
                Action::make('invite')
                    ->label(__('admin.actions.invite'))
                    ->schema([
                        TextInput::make('email')
                            ->label(__('admin.fields.email'))
                            ->email()
                            ->required(),
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
                EditAction::make(),
                Action::make('impersonate')
                    ->label(__('admin.actions.impersonate'))
                    ->icon('heroicon-o-user-circle')
                    ->action(function (User $record) {
                        Impersonation::start($record);

                        return redirect()->route('dashboard');
                    }),
                Action::make('open')
                    ->label(__('admin.actions.open'))
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
                DetachAction::make()
                    ->visible(fn (User $record): bool => ! $this->getOwnerRecord()->isLastOwner($record)),
            ]);
    }
}
