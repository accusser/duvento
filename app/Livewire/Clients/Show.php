<?php

namespace App\Livewire\Clients;

use App\Enums\AssetOwner;
use App\Enums\AssetPayer;
use App\Enums\AssetStatus;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Client;
use App\Rules\HttpWebsite;
use App\Support\ActivityLogger;
use App\Support\RateLimits;
use App\Support\UpcomingPayments;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    use InteractsWithWorkspace;

    #[Locked]
    public int $clientId;

    public bool $showClientForm = false;

    public bool $showRenew = false;

    public string $name = '';

    public string $contactName = '';

    public string $email = '';

    public string $website = '';

    public string $notes = '';

    public ?int $renewingId = null;

    public string $renewingName = '';

    public string $renewDate = '';

    public int $cashflowDays = 30;

    public function mount(int $client): void
    {
        $this->clientId = $this->workspace()->clients()->findOrFail($client)->id;
    }

    public function editClient(): void
    {
        $client = $this->client();
        $this->name = $client->name;
        $this->contactName = $client->contact_name ?? '';
        $this->email = $client->email ?? '';
        $this->website = $client->website ?? '';
        $this->notes = $client->notes ?? '';
        $this->showClientForm = true;
    }

    public function saveClient(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'contactName' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255', new HttpWebsite],
            'notes' => ['nullable', 'string'],
        ]);

        $client = $this->client();
        $client->update([
            'name' => $validated['name'],
            'contact_name' => $validated['contactName'] ?: null,
            'email' => $validated['email'] ?: null,
            'website' => $validated['website'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ]);
        ActivityLogger::log($this->workspace(), 'client.updated', $client, ['name' => $client->name]);
        $this->closeForms();
        $this->toast(__('app.flash.client_saved'));
    }

    public function deleteClient()
    {
        $this->assertOwner();
        $client = $this->client();
        ActivityLogger::log($this->workspace(), 'client.deleted', $client, ['name' => $client->name]);
        $client->delete();
        $this->flashToast(__('app.flash.client_deleted'), 'delete');

        return $this->redirect(route('clients'), navigate: true);
    }

    public function beginRenew(int $id): void
    {
        $asset = $this->asset($id);
        $this->renewingId = $asset->id;
        $this->renewingName = $asset->name;
        $this->renewDate = ($asset->expires_at ?? now())->copy()->addYear()->toDateString();
        $this->showRenew = true;
    }

    public function setCashflowDays(int $days): void
    {
        $this->cashflowDays = UpcomingPayments::normalizePeriod($days);
    }

    public function confirmRenew(): void
    {
        $this->validate([
            'renewingId' => ['required', 'integer'],
            'renewDate' => ['required', 'date'],
        ]);

        $asset = $this->asset((int) $this->renewingId);
        $from = $asset->expires_at?->toDateString();
        $asset->update(['expires_at' => $this->renewDate]);
        ActivityLogger::log($this->workspace(), 'asset.renewed', $asset, [
            'name' => $asset->name,
            'from' => $from,
            'to' => $this->renewDate,
        ]);
        $this->closeForms();
        $this->toast(__('app.flash.renewed'));
    }

    public function createPublicLink(): void
    {
        $this->assertOwner();
        $client = $this->client();

        if ($client->public_token) {
            return;
        }

        RateLimits::hitOrFail('share:'.$this->workspace()->id, 10, 60, 'share');
        $client->issuePublicToken();
        ActivityLogger::log($this->workspace(), 'client.share_created', $client, ['name' => $client->name]);
        $this->toast(__('app.flash.share_created'));
    }

    public function regeneratePublicLink(): void
    {
        $this->assertOwner();
        RateLimits::hitOrFail('share:'.$this->workspace()->id, 10, 60, 'share');
        $client = $this->client();
        $client->issuePublicToken();
        ActivityLogger::log($this->workspace(), 'client.share_regenerated', $client, ['name' => $client->name]);
        $this->toast(__('app.flash.share_regenerated'));
    }

    public function disablePublicLink(): void
    {
        $this->assertOwner();
        $client = $this->client();
        $client->revokePublicToken();
        ActivityLogger::log($this->workspace(), 'client.share_revoked', $client, ['name' => $client->name]);
        $this->toast(__('app.flash.share_revoked'));
    }

    public function deleteAsset(int $id): void
    {
        $this->assertOwner();
        $asset = $this->asset($id);
        ActivityLogger::log($this->workspace(), 'asset.deleted', $asset, ['name' => $asset->name]);
        $asset->delete();
        $this->toast(__('app.flash.asset_deleted'), 'delete');
    }

    #[On('close-modal')]
    public function closeForms(): void
    {
        $this->showClientForm = false;
        $this->reset(['showRenew', 'renewingId', 'renewingName', 'renewDate']);
        $this->resetValidation();
    }

    public function render()
    {
        $client = $this->client()->load(['assets.assetType']);
        $assets = $client->assets
            ->sortBy(fn ($asset) => $asset->expires_at?->timestamp ?? PHP_INT_MAX)
            ->values();
        $worst = $assets->sortByDesc(fn ($asset) => $asset->status->rank())->first();
        $highRisk = $assets->filter(fn ($asset) => $asset->status->rank() >= 4)->count();
        $this->cashflowDays = UpcomingPayments::normalizePeriod($this->cashflowDays);
        $cashflow = UpcomingPayments::summarize($assets, $this->cashflowDays, $this->workspace()->currencyCode());

        return view('livewire.clients.show', [
            'client' => $client,
            'assets' => $assets,
            'worstStatus' => $worst?->status ?? AssetStatus::Unknown,
            'highRisk' => $highRisk,
            'upcoming' => $assets->filter(fn ($asset) => $asset->status === AssetStatus::Upcoming)->count(),
            'unknownOwner' => $assets->filter(fn ($asset) => $asset->owner === AssetOwner::Unknown)->count(),
            'unknownPayer' => $assets->filter(fn ($asset) => $asset->payer === AssetPayer::Unknown)->count(),
            'cashflow' => $cashflow,
        ])->title(__('app.titles.client_show', ['name' => $client->name]));
    }

    private function client(): Client
    {
        return $this->workspace()->clients()->findOrFail($this->clientId);
    }

    private function asset(int $id)
    {
        return $this->workspace()->assets()->where('client_id', $this->clientId)->findOrFail($id);
    }
}
