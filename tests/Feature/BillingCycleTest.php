<?php

namespace Tests\Feature;

use App\Enums\WorkspacePlan;
use App\Models\User;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_pay_cancel_cycle(): void
    {
        config(['edition.edition' => 'cloud']);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Cloud Studio', $user);

        $this->assertSame(WorkspacePlan::FreeTrial, $workspace->fresh()->plan);

        $this->actingAs($user)
            ->get(route('billing.simulate', ['plan' => 'agency', 'workspace' => $workspace->id]))
            ->assertRedirect(route('settings.billing'));

        $this->assertSame(WorkspacePlan::Agency, $workspace->fresh()->plan);
        $this->assertTrue(\App\Support\Edition::enabled('white_label', $workspace->fresh()));
        $this->assertDatabaseHas('payment_events', ['type' => 'paid', 'workspace_id' => $workspace->id]);

        $this->actingAs($user)
            ->post(route('billing.cancel'))
            ->assertRedirect(route('settings.billing'));

        $this->assertSame(WorkspacePlan::FreeTrial, $workspace->fresh()->plan);
        $this->assertDatabaseHas('payment_events', ['type' => 'canceled', 'workspace_id' => $workspace->id]);
    }

    public function test_waitlist_signup(): void
    {
        $this->post(route('waitlist.store'), [
            'email' => 'agency@example.com',
            'name' => 'Studio',
        ])->assertRedirect();

        $this->assertDatabaseHas('waitlist_signups', ['email' => 'agency@example.com']);
    }

    public function test_trial_client_limit(): void
    {
        config([
            'edition.edition' => 'cloud',
            'billing.plans.free-trial.clients' => 1,
        ]);

        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Limited Studio', $user);
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Clients\Index::class)
            ->call('create')
            ->set('name', 'Первый')
            ->call('save');

        Livewire::test(\App\Livewire\Clients\Index::class)
            ->call('create')
            ->set('name', 'Второй')
            ->call('save')
            ->assertHasErrors(['name']);

        $this->assertSame(1, $user->currentWorkspace->clients()->count());
    }

    public function test_paddle_webhook_activates_subscription(): void
    {
        config(['edition.edition' => 'cloud']);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Paddle Studio', $user);

        $this->postJson(route('billing.paddle.webhook'), [
            'event_type' => 'subscription.activated',
            'data' => [
                'id' => 'sub_test_1',
                'custom_data' => [
                    'workspace_id' => $workspace->id,
                    'plan' => 'agency',
                ],
                'details' => ['totals' => ['grand_total' => 4900]],
            ],
        ])->assertOk();

        $this->assertSame(WorkspacePlan::Agency, $workspace->fresh()->plan);
        $this->assertDatabaseHas('subscriptions', [
            'workspace_id' => $workspace->id,
            'billing_provider_id' => 'sub_test_1',
        ]);
    }

    public function test_expired_trial_blocks_new_clients(): void
    {
        config(['edition.edition' => 'cloud']);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Expired Studio', $user);
        $workspace->subscriptions()->latest()->first()->update(['trial_ends_at' => now()->subDay()]);

        $this->artisan('duvento:expire-trials')->assertSuccessful();
        $this->assertSame(
            \App\Enums\SubscriptionStatus::PastDue,
            $workspace->subscriptions()->latest()->first()->fresh()->status,
        );

        $this->actingAs($user);

        Livewire::test(\App\Livewire\Clients\Index::class)
            ->call('create')
            ->set('name', 'После триала')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_trial_asset_limit(): void
    {
        config([
            'edition.edition' => 'cloud',
            'billing.plans.free-trial.assets' => 1,
        ]);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Asset Limit Studio', $user);
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $type = \App\Models\AssetType::query()->where('key', 'domain')->first();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Assets\Index::class)
            ->call('create')
            ->set('formClientId', $client->id)
            ->set('assetTypeId', $type->id)
            ->set('name', 'one.test')
            ->call('save');

        Livewire::test(\App\Livewire\Assets\Index::class)
            ->call('create')
            ->set('formClientId', $client->id)
            ->set('assetTypeId', $type->id)
            ->set('name', 'two.test')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_paddle_gateway_builds_checkout_url(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'sandbox-api.paddle.com/transactions' => \Illuminate\Support\Facades\Http::response([
                'data' => ['checkout' => ['url' => 'https://sandbox-buy.paddle.com/tx_test']],
            ]),
        ]);

        config([
            'paddle.api_key' => 'test_key',
            'paddle.sandbox' => true,
            'paddle.prices.agency' => 'pri_test',
        ]);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Checkout Studio', $user);
        $url = (new \Duvento\Cloud\Billing\PaddleBillingGateway)->checkoutUrl($workspace, WorkspacePlan::Agency);

        $this->assertSame('https://sandbox-buy.paddle.com/tx_test', $url);
    }
}
