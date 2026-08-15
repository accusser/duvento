<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkspaceApiToken;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CloudApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bearer_token_lists_assets_and_mcp_tools(): void
    {
        $this->skipWithoutCloud();
        config(['edition.edition' => 'cloud']);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Api Studio', $user);
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $type = \App\Models\AssetType::query()->where('key', 'domain')->first();
        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'api.test',
            'expires_at' => now()->addDays(10),
        ]);

        $plain = WorkspaceApiToken::generatePlain();
        $workspace->apiTokens()->create([
            'name' => 'ci',
            'token_hash' => WorkspaceApiToken::hash($plain),
        ]);

        $this->getJson('/api/v1/health')->assertOk()->assertJson(['ok' => true]);

        $this->getJson('/api/v1/assets', ['Authorization' => 'Bearer '.$plain])
            ->assertOk()
            ->assertJsonFragment(['name' => 'api.test']);

        $this->postJson('/api/v1/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ], ['Authorization' => 'Bearer '.$plain])
            ->assertOk()
            ->assertJsonPath('result.tools.0.name', 'list_clients');
    }

    public function test_webhook_fires_on_client_create(): void
    {
        $this->skipWithoutCloud();
        config(['edition.edition' => 'cloud']);
        Http::fake();

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Hook Studio', $user);
        $workspace->webhookEndpoints()->create([
            'url' => 'https://hooks.test/duvento',
            'secret' => 'secret',
            'events' => ['*'],
            'active' => true,
        ]);

        $this->actingAs($user);
        Livewire::test(\App\Livewire\Clients\Index::class)
            ->call('create')
            ->set('name', 'Hook Client')
            ->call('save');

        Http::assertSent(fn ($request) => $request->url() === 'https://hooks.test/duvento'
            && $request->hasHeader('X-Duvento-Signature'));
    }
}
