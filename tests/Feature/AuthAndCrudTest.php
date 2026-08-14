<?php

namespace Tests\Feature;

use App\Models\AssetType;
use App\Models\User;
use App\Support\SystemCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthAndCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_see_dashboard(): void
    {
        Livewire::test(\App\Livewire\Auth\Register::class)
            ->set('name', 'Иван')
            ->set('email', 'ivan@agency.test')
            ->set('workspace', 'Иван Студия')
            ->set('password', 'password12')
            ->set('password_confirmation', 'password12')
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'ivan@agency.test']);
        $this->assertDatabaseHas('workspaces', ['name' => 'Иван Студия']);
    }

    public function test_client_and_asset_appear_on_dashboard(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        app(\App\Support\WorkspaceProvisioner::class)->create('Studio', $user);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\Clients\Index::class)
            ->call('create')
            ->set('name', 'Клиент А')
            ->set('email', 'a@client.test')
            ->call('save');

        $client = $user->currentWorkspace->clients()->first();
        $ssl = AssetType::query()->where('key', 'ssl')->first();

        Livewire::test(\App\Livewire\Assets\Index::class)
            ->call('create')
            ->set('formClientId', $client->id)
            ->set('assetTypeId', $ssl->id)
            ->set('name', 'example.com')
            ->set('expiresAt', now()->addDays(5)->toDateString())
            ->set('owner', 'agency')
            ->set('payer', 'agency')
            ->call('save');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('Critical');
    }

    public function test_csv_export_downloads(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'alex@severnaya.example')->first();

        $response = $this->actingAs($user)->get(route('assets.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('nordic-atelier.ru', $response->streamedContent());
    }
}
