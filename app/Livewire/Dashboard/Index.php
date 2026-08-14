<?php

namespace App\Livewire\Dashboard;

use App\Enums\AssetStatus;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\AssetQuery;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Дашборд — Duvento')]
class Index extends Component
{
    use InteractsWithWorkspace;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $clientId = '';

    public function filterStatus(string $status): void
    {
        $this->status = $this->status === $status ? '' : $status;
    }

    public function render()
    {
        $workspace = $this->workspace();
        $counts = AssetQuery::counts($workspace);
        $assets = AssetQuery::filtered(
            $workspace,
            $this->search,
            $this->status ?: null,
            $this->clientId !== '' ? (int) $this->clientId : null,
        );

        return view('livewire.dashboard.index', [
            'counts' => $counts,
            'assets' => $assets,
            'clients' => $workspace->clients()->orderBy('name')->get(),
            'pulseTotal' => max(1, $counts['ok'] + $counts['upcoming'] + $counts['urgent'] + $counts['critical']),
            'statusKeys' => AssetStatus::dashboardKeys(),
        ]);
    }
}
