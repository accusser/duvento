<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\ClientReport;
use App\Support\Edition;
use App\Support\Onboarding;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;

    public function mount(): void
    {
        Onboarding::complete($this->workspace(), 'report');

        $id = (int) request('clientId');

        if ($id > 0 && $this->workspace()->clients()->whereKey($id)->exists()) {
            $this->redirect(route('reports.show', $id), navigate: true);
        }
    }

    public function render()
    {
        $workspace = $this->workspace();
        $clients = $workspace->clients()->with(['assets.assetType'])->orderBy('name')->get();

        return view('livewire.reports.index', [
            'groups' => $clients->map(fn ($client) => [
                'client' => $client,
                'counts' => ClientReport::counts($client),
            ]),
            'whiteLabel' => Edition::enabled('white_label', $workspace),
        ])->title(__('app.titles.reports'));
    }
}
