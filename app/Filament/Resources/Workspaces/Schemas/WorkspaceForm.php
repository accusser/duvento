<?php

namespace App\Filament\Resources\Workspaces\Schemas;

use App\Enums\WorkspacePlan;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WorkspaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(160),
                Select::make('plan')
                    ->options(collect(WorkspacePlan::cases())->mapWithKeys(fn ($p) => [$p->value => $p->value]))
                    ->required(),
                DateTimePicker::make('blocked_at')->label('Заблокирован'),
            ]);
    }
}
