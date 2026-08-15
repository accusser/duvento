<?php

namespace App\Filament\Resources\Workspaces;

use App\Filament\Concerns\HasAdminLexicon;
use App\Filament\Resources\Workspaces\Pages\CreateWorkspace;
use App\Filament\Resources\Workspaces\Pages\EditWorkspace;
use App\Filament\Resources\Workspaces\Pages\ListWorkspaces;
use App\Filament\Resources\Workspaces\RelationManagers\AssetsRelationManager;
use App\Filament\Resources\Workspaces\RelationManagers\ClientsRelationManager;
use App\Filament\Resources\Workspaces\RelationManagers\InvitationsRelationManager;
use App\Filament\Resources\Workspaces\RelationManagers\ReminderRulesRelationManager;
use App\Filament\Resources\Workspaces\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Workspaces\Schemas\WorkspaceForm;
use App\Filament\Resources\Workspaces\Tables\WorkspacesTable;
use App\Models\Workspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkspaceResource extends Resource
{
    use HasAdminLexicon;

    protected static ?string $model = Workspace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?int $navigationSort = 1;

    public static function adminLexicon(): string
    {
        return 'admin.resources.workspaces';
    }

    public static function form(Schema $schema): Schema
    {
        return WorkspaceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkspacesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
            InvitationsRelationManager::class,
            ClientsRelationManager::class,
            AssetsRelationManager::class,
            ReminderRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkspaces::route('/'),
            'create' => CreateWorkspace::route('/create'),
            'edit' => EditWorkspace::route('/{record}/edit'),
        ];
    }
}
