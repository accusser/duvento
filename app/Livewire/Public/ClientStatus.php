<?php

namespace App\Livewire\Public;

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class ClientStatus extends Component
{
    public string $token;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->client();
    }

    public function render()
    {
        $client = $this->client();
        $assets = $client->assets
            ->sortBy(fn ($asset) => $asset->expires_at?->timestamp ?? PHP_INT_MAX)
            ->values();

        return view('livewire.public.client-status', [
            'clientName' => $client->name,
            'agency' => $client->workspace->name,
            'assets' => $assets,
        ])->title(__('app.titles.share', ['name' => $client->name]));
    }

    private function client(): Client
    {
        return Client::query()
            ->where('public_token', $this->token)
            ->whereHas('workspace', fn ($query) => $query->whereNull('blocked_at'))
            ->with(['assets.assetType', 'workspace'])
            ->firstOrFail();
    }
}
