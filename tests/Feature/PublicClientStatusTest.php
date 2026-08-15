<?php

namespace Tests\Feature;

use App\Livewire\Clients\Show;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicClientStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_that_client_assets_and_status(): void
    {
        [$workspace, $client] = $this->workspaceWithClient();
        $other = $workspace->clients()->create(['name' => 'Другой', 'email' => 'hidden@agency.test', 'notes' => 'секрет']);
        $type = AssetType::query()->where('key', 'domain')->first();

        Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'acme.test',
            'expires_at' => now()->addDays(5),
            'notes' => 'внутреннее',
            'notice_email' => 'ops@agency.test',
        ]);
        Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $other->id,
            'asset_type_id' => $type->id,
            'name' => 'other-secret.test',
            'expires_at' => now()->addDays(3),
        ]);

        $token = $client->issuePublicToken();

        $this->get(route('share.show', $token))
            ->assertOk()
            ->assertSee('Статус активов — предоставлено Studio')
            ->assertSee('Acme')
            ->assertSee('acme.test')
            ->assertSee('Критично')
            ->assertDontSee('other-secret.test')
            ->assertDontSee('hidden@agency.test')
            ->assertDontSee('секрет')
            ->assertDontSee('внутреннее')
            ->assertDontSee('ops@agency.test')
            ->assertDontSee(__('app.common.edit'))
            ->assertDontSee(__('app.assets.renew'));
    }

    public function test_create_disable_and_regenerate_link(): void
    {
        [$workspace, $client] = $this->workspaceWithClient();
        $user = $workspace->users()->first();
        $this->actingAs($user);

        Livewire::test(Show::class, ['client' => $client->id])
            ->call('createPublicLink')
            ->assertHasNoErrors();

        $token = $client->fresh()->public_token;
        $this->assertNotNull($token);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{40}$/', $token);
        $this->get(route('share.show', $token))->assertOk();

        Livewire::test(Show::class, ['client' => $client->id])
            ->call('regeneratePublicLink')
            ->assertHasNoErrors();

        $newToken = $client->fresh()->public_token;
        $this->assertNotSame($token, $newToken);
        $this->get(route('share.show', $token))->assertNotFound();
        $this->get(route('share.show', $newToken))->assertOk();

        Livewire::test(Show::class, ['client' => $client->id])
            ->call('disablePublicLink')
            ->assertHasNoErrors();

        $this->assertNull($client->fresh()->public_token);
        $this->get(route('share.show', $newToken))->assertNotFound();
    }

    public function test_unknown_or_short_token_is_not_found(): void
    {
        $this->get('/s/'.str_repeat('a', 40))->assertNotFound();
        $this->get('/s/1')->assertNotFound();
    }

    private function workspaceWithClient(): array
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $user);
        $client = $workspace->clients()->create(['name' => 'Acme']);

        return [$workspace, $client];
    }
}
