<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Support\ActivityAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    protected string $view = 'filament.resources.activity-logs.view';

    public function getTitle(): string|Htmlable
    {
        return ActivityAction::label((string) $this->record->action);
    }
}
