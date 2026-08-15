<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewClient extends ViewRecord
{
    use HasAdminSubheading;

    protected static string $resource = ClientResource::class;

    protected string $view = 'filament.resources.clients.view';

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->record->load(['workspace', 'assets.assetType']);
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
