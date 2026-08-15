<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Livewire\Admin\GlobalSearch as AdminGlobalSearch;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Search\GlobalSearch as WorkspaceGlobalSearch;
use App\Models\AdminUser;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AssetQuery;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_finds_clients_regardless_of_case(): void
    {
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Студия', $user);
        $workspace->clients()->create(['name' => 'Северная Пекарня']);
        $this->actingAs($user);

        foreach (['северная', 'СЕВЕРНАЯ', 'Пекарня'] as $search) {
            $found = Livewire::test(ClientsIndex::class)->set('search', $search)->viewData('clients');

            $this->assertSame(['Северная Пекарня'], $found->pluck('name')->all(), $search);
        }

        $this->assertCount(0, Livewire::test(ClientsIndex::class)->set('search', 'южная')->viewData('clients'));
    }

    public function test_client_finds_assets_regardless_of_case(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceWithAsset($user, 'ОСАГО Пекарни');

        $this->assertSame(['ОСАГО Пекарни'], AssetQuery::filtered($workspace, 'осаго')->pluck('name')->all());
        $this->assertSame(['ОСАГО Пекарни'], AssetQuery::filtered($workspace, 'пекарни')->pluck('name')->all());
        $this->assertCount(0, AssetQuery::filtered($workspace, 'каско'));
    }

    public function test_admin_search_page_links_every_group_regardless_of_case(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Северный Искатель']);
        $workspace = app(WorkspaceProvisioner::class)->create('Северная Студия', $user);
        $client = $workspace->clients()->create(['name' => 'Северная Пекарня']);
        $this->asset($workspace, $client->id, 'Северный Домен');

        $this->actingAs($admin, 'admin')
            ->get('/admin/search?q='.urlencode('северн'))
            ->assertOk()
            ->assertSee('Северная Студия')
            ->assertSee('Северный Искатель')
            ->assertSee('Северная Пекарня')
            ->assertSee('Северный Домен');

        $this->actingAs($admin, 'admin')
            ->get('/admin/search?q='.urlencode('СЕВЕРНАЯ ПЕКАРНЯ'))
            ->assertOk()
            ->assertSee('Северная Пекарня');
    }

    public function test_admin_table_search_finds_records_regardless_of_case(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Студия', $user);
        $client = $workspace->clients()->create(['name' => 'Северная Пекарня']);

        $this->actingAs($admin, 'admin');

        Livewire::test(ListClients::class)
            ->searchTable('северная')
            ->assertCanSeeTableRecords([$client]);
    }

    public function test_admin_topbar_suggests_while_typing(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Северный Искатель']);
        $workspace = app(WorkspaceProvisioner::class)->create('Северная Студия', $user);

        $this->actingAs($admin, 'admin');

        Livewire::test(AdminGlobalSearch::class)
            ->assertDontSee($workspace->name)
            ->set('q', 'с')
            ->assertDontSee($workspace->name)
            ->set('q', 'северн')
            ->assertSee('search-suggest-item', false)
            ->assertSee('Северная Студия')
            ->assertSee('Северный Искатель')
            ->set('q', 'южн')
            ->assertSee(__('admin.search.empty'));
    }

    public function test_topbars_render_live_search_inputs(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('wire:model.live.debounce.300ms="q"', false)
            ->assertSee('action="'.url('/admin/search').'"', false);

        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Студия', $user);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('wire:model.live.debounce.300ms="q"', false)
            ->assertSee('action="'.route('assets').'"', false);
    }

    public function test_topbars_suggest_sections_by_name(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(AdminGlobalSearch::class)
            ->set('q', 'Клие')
            ->assertSee(__('admin.header.sections'))
            ->assertSee(__('admin.resources.clients.plural'));

        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Студия', $user);
        $this->actingAs($user);

        Livewire::test(WorkspaceGlobalSearch::class)
            ->set('q', 'Акт')
            ->assertSee(__('app.nav.sections'))
            ->assertSee(__('app.nav.assets'));
    }

    public function test_client_topbar_hides_owner_only_sections_from_members(): void
    {
        $owner = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Студия', $owner);
        $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->users()->attach($member, ['role' => WorkspaceRole::Member]);

        $this->actingAs($member);

        Livewire::test(WorkspaceGlobalSearch::class)
            ->set('q', __('app.nav.import'))
            ->assertDontSee(route('import'));
    }

    public function test_client_topbar_suggests_while_typing(): void
    {
        $user = User::factory()->create();
        $workspace = $this->workspaceWithAsset($user, 'ОСАГО Пекарни');
        $workspace->clients()->create(['name' => 'Северная Пекарня']);

        $this->actingAs($user);

        Livewire::test(WorkspaceGlobalSearch::class)
            ->set('q', 'пекарн')
            ->assertSee('ОСАГО Пекарни')
            ->assertSee('Северная Пекарня')
            ->set('q', 'каско')
            ->assertSee(__('app.header.search_empty'));
    }

    public function test_client_topbar_only_suggests_own_workspace(): void
    {
        $user = User::factory()->create();
        $this->workspaceWithAsset($user, 'Свой Домен');

        $stranger = User::factory()->create();
        $this->workspaceWithAsset($stranger, 'Чужой Домен');

        $this->actingAs($user);

        Livewire::test(WorkspaceGlobalSearch::class)
            ->set('q', 'домен')
            ->assertSee('Свой Домен')
            ->assertDontSee('Чужой Домен');
    }

    private function admin(): AdminUser
    {
        $this->seed();

        return AdminUser::query()->where('email', 'admin@duvento.local')->firstOrFail();
    }

    private function workspaceWithAsset(User $user, string $name): Workspace
    {
        $workspace = app(WorkspaceProvisioner::class)->create('Студия', $user);
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $this->asset($workspace, $client->id, $name);

        return $workspace;
    }

    private function asset(Workspace $workspace, int $clientId, string $name): Asset
    {
        return Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $clientId,
            'asset_type_id' => AssetType::query()->value('id'),
            'name' => $name,
            'expires_at' => now()->addDays(30),
        ]);
    }
}
