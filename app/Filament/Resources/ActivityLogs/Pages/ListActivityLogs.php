<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Concerns\ExportsAdminCsv;
use App\Filament\Concerns\HasAdminSubheading;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListActivityLogs extends ListRecords
{
    use ExportsAdminCsv;
    use HasAdminSubheading;

    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportCsvAction('duvento-activity', [
                __('admin.fields.when'),
                __('admin.fields.workspace'),
                __('admin.fields.who'),
                __('admin.fields.action'),
                __('admin.fields.properties'),
            ], fn ($record) => [
                $record->created_at?->toDateTimeString(),
                $record->workspace?->name,
                $record->actorName(),
                $record->action,
                is_array($record->properties) ? json_encode($record->properties, JSON_UNESCAPED_UNICODE) : $record->properties,
            ]),
            Action::make('clear')
                ->label(__('admin.actions.clear_log'))
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->iconButton()
                ->tooltip(__('admin.actions.clear_log'))
                ->disabled(fn (): bool => ! ActivityLog::query()->exists())
                ->requiresConfirmation()
                ->modalHeading(__('admin.actions.clear_log'))
                ->modalDescription(__('admin.actions.confirm_clear_log'))
                ->modalSubmitActionLabel(__('admin.actions.clear_log'))
                ->action(function (): void {
                    ActivityLog::query()->delete();

                    Notification::make()
                        ->title(__('admin.actions.cleared_log'))
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function eagerLoadExport(Builder $query): void
    {
        $query->with(['workspace', 'user', 'adminUser']);
    }
}
