<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\WorkspacePlan;
use App\Livewire\Assets\Form;
use App\Livewire\Clients\Index;
use App\Models\AssetType;
use App\Models\User;
use App\Support\Edition;
use App\Support\WorkspaceProvisioner;
use Duvento\Cloud\Billing\PaddleBillingGateway;
use Duvento\Cloud\Reports\WhiteLabelReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        $this->assertTrue(Edition::enabled('white_label', $workspace->fresh()));
        $this->assertDatabaseHas('payment_events', ['type' => 'paid', 'workspace_id' => $workspace->id]);

        $this->actingAs($user)
            ->post(route('billing.cancel'))
            ->assertRedirect(route('settings.billing'));

        $this->assertSame(WorkspacePlan::FreeTrial, $workspace->fresh()->plan);
        $this->assertDatabaseHas('payment_events', ['type' => 'canceled', 'workspace_id' => $workspace->id]);
    }

    public function test_agency_white_label_pdf_and_signed_link(): void
    {
        $this->skipWithoutCloud();
        config(['edition.edition' => 'cloud']);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Report Studio', $user);
        $this->actingAs($user)
            ->get(route('billing.simulate', ['plan' => 'agency', 'workspace' => $workspace->id]));

        $this->actingAs($user)
            ->get(route('reports.clients'))
            ->assertOk()
            ->assertSee('Скачать PDF');

        $this->actingAs($user)
            ->get(route('reports.clients.pdf'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $url = app(WhiteLabelReport::class)->signedUrl($workspace->fresh());

        $this->get($url)->assertOk()->assertSee($workspace->name);
        $this->get(route('reports.shared', $workspace))->assertForbidden();
    }

    public function test_waitlist_signup(): void
    {
        config(['edition.edition' => 'cloud']);

        $this->post(route('waitlist.store'), [
            'email' => 'agency@example.com',
            'name' => 'Studio',
        ])->assertRedirect();

        $this->assertDatabaseHas('waitlist_signups', ['email' => 'agency@example.com']);
    }

    public function test_cloud_landing_shows_pricing_and_waitlist(): void
    {
        config(['edition.edition' => 'cloud']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('$19')
            ->assertSee('Join waitlist');
    }

    public function test_simulate_blocked_in_production(): void
    {
        config(['edition.edition' => 'cloud', 'paddle.api_key' => null]);
        $this->app['env'] = 'production';

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Prod Studio', $user);

        $this->actingAs($user)
            ->get(route('billing.simulate', ['plan' => 'agency', 'workspace' => $workspace->id]))
            ->assertNotFound();
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

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Первый')
            ->call('save');

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Второй')
            ->call('save')
            ->assertHasErrors(['name']);

        $this->assertSame(1, $user->currentWorkspace->clients()->count());
    }

    public function test_paddle_webhook_activates_subscription(): void
    {
        $this->skipWithoutCloud();
        config(['edition.edition' => 'cloud']);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Paddle Studio', $user);

        $this->postPaddleWebhook([
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
            SubscriptionStatus::PastDue,
            $workspace->subscriptions()->latest()->first()->fresh()->status,
        );

        $this->actingAs($user);

        Livewire::test(Index::class)
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
        $type = AssetType::query()->where('key', 'domain')->first();
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('formClientId', $client->id)
            ->set('assetTypeId', $type->id)
            ->set('name', 'one.test')
            ->call('save');

        Livewire::test(Form::class)
            ->set('formClientId', $client->id)
            ->set('assetTypeId', $type->id)
            ->set('name', 'two.test')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_paddle_gateway_builds_checkout_url(): void
    {
        $this->skipWithoutCloud();
        Http::fake([
            'sandbox-api.paddle.com/transactions' => Http::response([
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
        $url = (new PaddleBillingGateway)->checkoutUrl($workspace, WorkspacePlan::Agency);

        $this->assertSame('https://sandbox-buy.paddle.com/tx_test', $url);
    }
}
