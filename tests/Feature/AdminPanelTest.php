<?php

namespace Tests\Feature;

use App\Enums\WorkspacePlan;
use App\Filament\Resources\Workspaces\Pages\ListWorkspaces;
use App\Models\AdminUser;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_filament_workspaces(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin/workspaces')
            ->assertOk()
            ->assertSee('Северная студия');
    }

    public function test_admin_grant_agency_activates_subscription(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();
        $workspace = Workspace::query()->where('name', 'Северная студия')->first();

        $this->actingAs($admin, 'admin');

        Livewire::test(ListWorkspaces::class)
            ->callTableAction('grantAgency', $workspace);

        $this->assertSame(WorkspacePlan::Agency, $workspace->fresh()->plan);
        $this->assertDatabaseHas('subscriptions', [
            'workspace_id' => $workspace->id,
            'billing_provider_id' => 'manual_admin',
        ]);
    }

    public function test_admin_can_open_payment_events(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin/payment-events')
            ->assertOk();
    }
}
