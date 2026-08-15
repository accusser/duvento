<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\WebhookEndpoint;
use App\Models\WorkspaceApiToken;
use App\Support\Edition;
use App\Support\PublicHttpUrl;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Api extends Component
{
    use InteractsWithWorkspace;

    public string $tokenName = 'default';

    public string $webhookUrl = '';

    public ?string $plainToken = null;

    public function mount(): void
    {
        abort_unless(Edition::enabled('public_api') || Edition::enabled('webhooks'), 404);
        $this->assertOwner();
        $this->webhookUrl = $this->workspace()->webhookEndpoints()->latest()->first()?->url ?? '';
    }

    public function createToken(): void
    {
        abort_unless(Edition::enabled('public_api'), 404);
        $this->assertOwner();
        $this->validate(['tokenName' => ['required', 'string', 'max:80']]);

        $plain = WorkspaceApiToken::generatePlain();
        $this->workspace()->apiTokens()->create([
            'name' => $this->tokenName,
            'token_hash' => WorkspaceApiToken::hash($plain),
        ]);
        $this->plainToken = $plain;
    }

    public function deleteToken(int $id): void
    {
        $this->assertOwner();
        $this->workspace()->apiTokens()->where('id', $id)->delete();
        $this->toast(__('app.flash.token_deleted'), 'delete');
    }

    public function saveWebhook(): void
    {
        abort_unless(Edition::enabled('webhooks'), 404);
        $this->assertOwner();
        $this->validate([
            'webhookUrl' => [
                'required',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! PublicHttpUrl::allows((string) $value)) {
                        $fail(__('app.api.https_only'));
                    }
                },
            ],
        ]);

        $workspace = $this->workspace();
        $existing = $workspace->webhookEndpoints()->latest()->first();

        if ($existing) {
            $existing->update(['url' => $this->webhookUrl, 'active' => true, 'events' => ['*']]);
        } else {
            $workspace->webhookEndpoints()->create([
                'url' => $this->webhookUrl,
                'secret' => WebhookEndpoint::generateSecret(),
                'events' => ['*'],
                'active' => true,
            ]);
        }

        $this->toast(__('app.flash.webhook_saved'));
    }

    public function render()
    {
        return view('livewire.settings.api', [
            'tokens' => $this->workspace()->apiTokens()->latest()->get(),
            'webhook' => $this->workspace()->webhookEndpoints()->latest()->first(),
        ])->title(__('app.titles.api'));
    }
}
