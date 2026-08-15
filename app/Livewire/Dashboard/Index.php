<?php

namespace App\Livewire\Dashboard;

use App\Enums\AssetOwner;
use App\Enums\AssetPayer;
use App\Enums\AssetStatus;
use App\Enums\AutoRenew;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\AssetType;
use App\Rules\HttpWebsite;
use App\Support\ActivityLogger;
use App\Support\AssetQuery;
use App\Support\Onboarding;
use App\Support\PlanGuard;
use App\Support\UpcomingPayments;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $clientId = '';

    #[Url(as: 'due')]
    public int $cashflowDays = 30;

    public bool $cashflowOpen = false;

    public string $quickClientName = '';

    public string $quickClientEmail = '';

    public string $quickAssetName = '';

    public ?int $quickAssetClientId = null;

    public ?int $quickAssetTypeId = null;

    public string $quickExpiresAt = '';

    public bool $showClientForm = false;

    public ?int $editingClientId = null;

    public string $clientName = '';

    public string $clientContactName = '';

    public string $clientEmail = '';

    public string $clientWebsite = '';

    public string $clientNotes = '';

    public function filterStatus(string $status): void
    {
        $this->cashflowOpen = false;
        $this->status = $this->status === $status ? '' : $status;
    }

    public function setCashflowDays(int $days): void
    {
        $this->cashflowDays = UpcomingPayments::normalizePeriod($days);
    }

    public function toggleCashflow(): void
    {
        $this->cashflowOpen = ! $this->cashflowOpen;

        if ($this->cashflowOpen) {
            $this->status = '';
        }
    }

    public function markStepDone(string $key): void
    {
        Onboarding::complete($this->workspace(), $key);
    }

    public function openStep(string $key): void
    {
        if ($key === 'report') {
            $this->redirect(route('reports'), navigate: true);

            return;
        }

        $id = Onboarding::targetId($this->workspace(), $key);

        if (in_array($key, ['client', 'notice'], true)) {
            $this->openClientForm($id);

            return;
        }

        if ($id) {
            $this->redirect(route('assets.edit', $id), navigate: true);

            return;
        }

        $this->redirect(route('assets.create', $key === 'check' ? ['ssl' => 1] : []), navigate: true);
    }

    public function openClientForm(?int $id = null): void
    {
        $this->closeForms();

        if ($id) {
            $client = $this->workspace()->clients()->findOrFail($id);
            $this->editingClientId = $client->id;
            $this->clientName = $client->name;
            $this->clientContactName = $client->contact_name ?? '';
            $this->clientEmail = $client->email ?? '';
            $this->clientWebsite = $client->website ?? '';
            $this->clientNotes = $client->notes ?? '';
        }

        $this->showClientForm = true;
    }

    public function saveClient(PlanGuard $guard)
    {
        $validated = $this->validate([
            'clientName' => ['required', 'string', 'max:160'],
            'clientContactName' => ['nullable', 'string', 'max:160'],
            'clientEmail' => ['nullable', 'email', 'max:255'],
            'clientWebsite' => ['nullable', 'string', 'max:255', new HttpWebsite],
            'clientNotes' => ['nullable', 'string'],
        ]);
        $workspace = $this->workspace();
        $payload = [
            'name' => $validated['clientName'],
            'contact_name' => $validated['clientContactName'] ?: null,
            'email' => $validated['clientEmail'] ?: null,
            'website' => $validated['clientWebsite'] ?: null,
            'notes' => $validated['clientNotes'] ?: null,
        ];

        if ($this->editingClientId) {
            $client = $workspace->clients()->findOrFail($this->editingClientId);
            $client->update($payload);
            ActivityLogger::log($workspace, 'client.updated', $client, ['name' => $client->name]);
        } else {
            $guard->assertCanCreateClient($workspace);
            $client = $workspace->clients()->create($payload);
            ActivityLogger::log($workspace, 'client.created', $client, ['name' => $client->name]);
            $this->flashToast(__('app.flash.client_added'));

            return $this->redirect(route('clients.show', $client), navigate: true);
        }

        $this->closeForms();
        $this->toast(__('app.flash.client_saved'));
    }

    #[On('close-modal')]
    public function closeForms(): void
    {
        $this->reset([
            'showClientForm', 'editingClientId',
            'clientName', 'clientContactName', 'clientEmail', 'clientWebsite', 'clientNotes',
        ]);
        $this->resetValidation();
    }

    public function saveQuickClient(PlanGuard $guard): void
    {
        $validated = $this->validate([
            'quickClientName' => ['required', 'string', 'max:160'],
            'quickClientEmail' => ['nullable', 'email', 'max:255'],
        ]);

        $workspace = $this->workspace();
        $guard->assertCanCreateClient($workspace);
        $client = $workspace->clients()->create([
            'name' => $validated['quickClientName'],
            'email' => $validated['quickClientEmail'] ?: null,
        ]);
        ActivityLogger::log($workspace, 'client.created', $client, ['name' => $client->name]);
        $this->quickAssetClientId = $client->id;
        $this->reset(['quickClientName', 'quickClientEmail']);
        $this->toast(__('app.flash.client_added'));
    }

    public function saveQuickAsset(PlanGuard $guard): void
    {
        $workspace = $this->workspace();
        $validated = $this->validate([
            'quickAssetClientId' => ['required', Rule::exists('clients', 'id')->where('workspace_id', $workspace->id)],
            'quickAssetTypeId' => ['required', Rule::exists('asset_types', 'id')],
            'quickAssetName' => ['required', 'string', 'max:255'],
            'quickExpiresAt' => ['nullable', 'date'],
        ]);

        $guard->assertCanCreateAsset($workspace);
        $asset = $workspace->assets()->create([
            'client_id' => $validated['quickAssetClientId'],
            'asset_type_id' => $validated['quickAssetTypeId'],
            'name' => $validated['quickAssetName'],
            'expires_at' => $validated['quickExpiresAt'] ?: null,
            'auto_renew' => AutoRenew::Unknown,
            'owner' => AssetOwner::Unknown,
            'payer' => AssetPayer::Unknown,
        ]);
        ActivityLogger::log($workspace, 'asset.created', $asset, ['name' => $asset->name]);
        $this->reset(['quickAssetName', 'quickExpiresAt']);
        $this->toast(__('app.flash.asset_added'));
    }

    public function render()
    {
        $workspace = $this->workspace();
        $this->cashflowDays = UpcomingPayments::normalizePeriod($this->cashflowDays);
        $counts = AssetQuery::counts($workspace);
        $extras = AssetQuery::extras($workspace);
        $steps = Onboarding::withStatus($workspace);
        $assets = AssetQuery::filtered(
            $workspace,
            $this->search,
            $this->status ?: null,
            $this->clientId !== '' ? (int) $this->clientId : null,
            $this->cashflowOpen ? ['cashflowDays' => $this->cashflowDays] : [],
        );
        $cashflow = UpcomingPayments::summarize(
            $workspace->assets,
            $this->cashflowDays,
            $workspace->currencyCode(),
        );
        $clients = $workspace->clients()->orderBy('name')->get();

        return view('livewire.dashboard.index', [
            'counts' => $counts,
            'extras' => $extras,
            'steps' => $steps,
            'remaining' => Onboarding::remaining($steps),
            'doneCount' => count($steps) - Onboarding::remaining($steps),
            'assets' => $assets,
            'cashflow' => $cashflow,
            'clients' => $clients,
            'types' => AssetType::query()->availableFor($workspace)->get()
                ->sortBy(fn (AssetType $type) => mb_strtolower($type->displayLabel())),
            'pulseTotal' => max(1, $counts['ok'] + $counts['upcoming'] + $counts['urgent'] + $counts['critical']),
            'statusKeys' => AssetStatus::dashboardKeys(),
        ])->title(__('app.titles.dashboard'));
    }
}
