<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Users\UserResource;
use App\Support\Impersonation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewUser extends ViewRecord
{
    use HasAdminSubheading;

    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.users.view';

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->record->load('workspaces');
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('impersonate')
                ->label(__('admin.actions.impersonate'))
                ->action(function () {
                    Impersonation::start($this->record);

                    return redirect()->route('dashboard');
                }),
            DeleteAction::make(),
        ];
    }
}
