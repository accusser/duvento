<?php

namespace Tests\Feature;

use App\Enums\AssetPayer;
use App\Livewire\Assets\Form;
use App\Livewire\Clients\Show;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Settings\Account;
use App\Models\AssetType;
use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;
use App\Support\SystemCatalog;
use App\Support\UpcomingPayments;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widget_sums_priced_assets_by_currency_and_payer(): void
    {
        [$user, $workspace, $client, $type] = $this->workspace();

        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'agency.com',
            'expires_at' => now()->addDays(10),
            'payer' => AssetPayer::Agency,
            'renewal_cost' => 120,
            'currency' => 'USD',
        ]);
        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'client.com',
            'expires_at' => now()->addDays(20),
            'payer' => AssetPayer::Client,
            'renewal_cost' => 80,
            'currency' => 'USD',
        ]);
        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'euro.com',
            'expires_at' => now()->addDays(5),
            'payer' => AssetPayer::Agency,
            'renewal_cost' => 40,
            'currency' => 'EUR',
        ]);
        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'unpriced.com',
            'expires_at' => now()->addDays(3),
        ]);
        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'later.com',
            'expires_at' => now()->addDays(60),
            'payer' => AssetPayer::Agency,
            'renewal_cost' => 500,
            'currency' => 'USD',
        ]);

        $this->actingAs($user);

        Livewire::test(DashboardIndex::class)
            ->assertSee(__('app.cashflow.title'))
            ->assertSee(UpcomingPayments::format('USD', 200))
            ->assertSee(UpcomingPayments::format('EUR', 40))
            ->assertSee(__('app.cashflow.agency_pays', ['amount' => UpcomingPayments::join(['EUR' => '40.00', 'USD' => '120.00'])]))
            ->assertSee(__('app.cashflow.client_pays', ['amount' => UpcomingPayments::format('USD', 80)]))
            ->assertSee('unpriced.com')
            ->assertSee('later.com')
            ->call('toggleCashflow')
            ->assertSee('agency.com')
            ->assertSee('client.com')
            ->assertSee('euro.com')
            ->assertDontSee('unpriced.com')
            ->assertDontSee('later.com')
            ->call('setCashflowDays', 7)
            ->assertSee('euro.com')
            ->assertDontSee('agency.com')
            ->assertSee(UpcomingPayments::format('EUR', 40));
    }

    public function test_client_page_is_scoped_to_that_client(): void
    {
        [$user, $workspace, $client, $type] = $this->workspace();
        $other = $workspace->clients()->create(['name' => 'Другой']);

        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'mine.com',
            'expires_at' => now()->addDays(8),
            'payer' => AssetPayer::Client,
            'renewal_cost' => 50,
            'currency' => 'USD',
        ]);
        $workspace->assets()->create([
            'client_id' => $other->id,
            'asset_type_id' => $type->id,
            'name' => 'theirs.com',
            'expires_at' => now()->addDays(8),
            'payer' => AssetPayer::Agency,
            'renewal_cost' => 999,
            'currency' => 'USD',
        ]);

        $this->actingAs($user);

        Livewire::test(Show::class, ['client' => $client->id])
            ->assertSee(UpcomingPayments::format('USD', 50))
            ->assertDontSee(UpcomingPayments::format('USD', 999))
            ->assertSee(__('app.cashflow.client_pays', ['amount' => UpcomingPayments::format('USD', 50)]));
    }

    public function test_asset_form_saves_renewal_cost_and_workspace_currency_default(): void
    {
        [$user, $workspace, $client, $type] = $this->workspace();
        $workspace->update(['currency' => 'EUR']);
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->assertSet('currency', 'EUR')
            ->set('formClientId', $client->id)
            ->set('assetTypeId', $type->id)
            ->set('name', 'priced.com')
            ->set('expiresAt', now()->addDays(12)->toDateString())
            ->set('renewalCost', '75.5')
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('assets', [
            'name' => 'priced.com',
            'renewal_cost' => 75.5,
            'currency' => 'EUR',
        ]);
    }

    public function test_empty_widget_does_not_pretend_the_sum_is_zero(): void
    {
        [$user] = $this->workspace();
        $this->actingAs($user);

        Livewire::test(DashboardIndex::class)
            ->assertSee(__('app.cashflow.caption_empty', ['days' => 30]))
            ->assertSee(__('app.cashflow.empty_hint'))
            ->assertDontSee(UpcomingPayments::format('USD', 0));
    }

    public function test_owner_can_change_workspace_currency(): void
    {
        [$user, $workspace] = $this->workspace();
        $this->actingAs($user);

        Livewire::test(Account::class)
            ->set('workspaceCurrency', 'RUB')
            ->call('saveWorkspace')
            ->assertHasNoErrors();

        $this->assertSame('RUB', $workspace->fresh()->currency);
    }

    /** @return array{0: User, 1: Workspace, 2: Client, 3: AssetType} */
    private function workspace(): array
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $user);
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $type = AssetType::query()->where('key', 'domain')->first();

        return [$user->fresh(), $workspace, $client, $type];
    }
}
