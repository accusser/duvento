<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewAsset extends ViewRecord
{
    use HasAdminSubheading;

    protected static string $resource = AssetResource::class;

    protected string $view = 'filament.resources.assets.view';

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->record->load(['workspace', 'client', 'assetType']);
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

    protected function getViewData(): array
    {
        return [
            'logs' => $this->record->activityLogs()
                ->with(['user', 'adminUser'])
                ->latest()
                ->limit(50)
                ->get(),
        ];
    }
}
