<?php

namespace App\Filament\Concerns;

use App\Support\CsvDownload;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsAdminCsv
{
    /**
     * @param  list<string>  $headers
     * @param  callable(mixed): list<mixed>  $row
     */
    protected function exportCsvAction(string $filename, array $headers, callable $row): Action
    {
        return Action::make('export')
            ->label(__('admin.actions.export'))
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () use ($filename, $headers, $row): StreamedResponse {
                $query = $this->getFilteredTableQuery();
                $this->eagerLoadExport($query);

                return CsvDownload::stream(
                    $filename.'-'.now()->format('Y-m-d').'.csv',
                    $headers,
                    $query->get()->map($row),
                );
            });
    }

    protected function eagerLoadExport(Builder $query): void
    {
        //
    }
}
