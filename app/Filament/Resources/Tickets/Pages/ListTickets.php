<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Concerns\ExportsAdminCsv;
use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTickets extends ListRecords
{
    use ExportsAdminCsv;
    use HasAdminSubheading;

    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportCsvAction('duvento-tickets', [
                __('admin.fields.workspace'),
                __('admin.tickets.subject'),
                __('admin.fields.status'),
                __('admin.tickets.priority'),
                __('admin.tickets.last_message'),
            ], fn ($record) => [
                $record->workspace?->name,
                $record->subject,
                $record->status->label(),
                $record->priority->label(),
                $record->last_message_at?->toDateTimeString(),
            ]),
        ];
    }

    protected function eagerLoadExport(Builder $query): void
    {
        $query->with('workspace');
    }
}
