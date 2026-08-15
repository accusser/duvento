<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Asset;
use App\Support\ClientReport;
use App\Support\Onboarding;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    use InteractsWithWorkspace;

    #[Locked]
    public int $clientId;

    public function mount(int $client): void
    {
        $this->clientId = $this->workspace()->clients()->findOrFail($client)->id;
        Onboarding::complete($this->workspace(), 'report');
    }

    public function render()
    {
        $client = $this->workspace()->clients()->with(['assets.assetType'])->findOrFail($this->clientId);
        $assets = ClientReport::assets($client);
        $assetIds = $assets->pluck('id');

        return view('livewire.reports.show', [
            'client' => $client,
            'assets' => $assets,
            'counts' => ClientReport::counts($client),
            'actions' => ClientReport::actions($client),
            'logs' => $this->workspace()->activityLogs()
                ->where('subject_type', Asset::class)
                ->whereIn('subject_id', $assetIds)
                ->whereIn('action', ['reminder.sent', 'ssl.check_failed', 'ssl.updated', 'asset.renewed'])
                ->with('user')
                ->latest()
                ->limit(20)
                ->get(),
        ])->title(__('app.titles.report_show', ['name' => $client->name]));
    }
}
