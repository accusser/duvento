<?php

namespace Tests\Feature;

use App\Enums\WorkspacePlan;
use App\Filament\Pages\EditAdminProfile;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClient;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Workspaces\Pages\CreateWorkspace;
use App\Filament\Resources\Workspaces\Pages\ListWorkspaces;
use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Workspace;
use App\Support\AppLocale;
use Filament\Actions\Testing\TestAction;
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

    public function test_admin_workspace_plan_uses_lexicon_labels(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();
        $workspace = Workspace::query()->where('name', 'Северная студия')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin/workspaces/'.$workspace->id.'/edit')
            ->assertOk()
            ->assertSee('Самохостинг')
            ->assertDontSee('Пробный период')
            ->assertDontSee('Starter')
            ->assertDontSee('Agency');
    }

    public function test_admin_grant_agency_activates_subscription(): void
    {
        config(['edition.edition' => 'cloud']);
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
        config(['edition.edition' => 'cloud']);
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin/payment-events')
            ->assertOk();
    }

    public function test_admin_can_open_profile(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Мой профиль')
            ->assertSee('Duvento Admin')
            ->assertSee('admin@duvento.local')
            ->assertSee('Сгенерировать пароль')
            ->assertSee('Не меньше 8 символов');

        $component = Livewire::test(EditAdminProfile::class)
            ->callAction(TestAction::make('generatePassword')->schemaComponent('password'));

        $this->assertSame(16, strlen($component->get('data.password')));
        $this->assertSame($component->get('data.password'), $component->get('data.passwordConfirmation'));
    }

    public function test_admin_subscriptions_uses_lexicon_title(): void
    {
        config(['edition.edition' => 'cloud']);
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertSee('Подписки');
    }

    public function test_admin_user_menu_has_profile_link(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('/admin/profile', false)
            ->assertSee('Мой профиль')
            ->assertSee('Выйти')
            ->assertSee('brand-mark', false)
            ->assertSee('data-toggle="sidebar"', false)
            ->assertSee('data-toggle="theme"', false)
            ->assertSee('Duvento')
            ->assertSee('Уведомления')
            ->assertDontSee('fi-theme-switcher', false);
    }

    public function test_admin_shell_navigates_without_full_page_reloads(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $html = $this->actingAs($admin, 'admin')
            ->get('/admin/workspaces')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="h-nav-item active" wire:navigate', $html);
        $this->assertMatchesRegularExpression('/<a href="[^"]*\/admin\/workspaces" wire:navigate>/', $html);

        foreach (AppLocale::codes() as $code) {
            $this->assertStringContainsString('/admin/locale/'.$code, $html);
            $this->assertStringNotContainsString('/admin/locale/'.$code.'" wire:navigate', $html);
        }
    }

    public function test_admin_body_layout_classes_come_from_cookie(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $bodyClasses = function (string $html): string {
            preg_match('/<body[^>]*class="([^"]*)"/s', $html, $matches);

            return $matches[1] ?? '';
        };

        $default = $bodyClasses(
            $this->actingAs($admin, 'admin')->get('/admin')->assertOk()->getContent()
        );

        $this->assertStringContainsString('layout-boxed', $default);
        $this->assertStringNotContainsString('sidebar-mini', $default);

        $mini = $bodyClasses(
            $this->actingAs($admin, 'admin')
                ->withUnencryptedCookie('nyvora-admin-layout', 'boxed-mini')
                ->get('/admin')
                ->assertOk()
                ->getContent()
        );

        $this->assertStringContainsString('sidebar-mini', $mini);
    }

    public function test_admin_workspace_validation_uses_lexicon(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();
        $this->actingAs($admin, 'admin');

        Livewire::test(CreateWorkspace::class)
            ->fillForm(['name' => ''])
            ->call('create')
            ->assertHasFormErrors(['name'])
            ->assertSee('Поле обязательно для заполнения.')
            ->assertDontSee('The name field is required.');
    }

    public function test_admin_can_switch_locale(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->from('/admin')
            ->get('/admin/locale/en')
            ->assertRedirect('/admin');

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Language')
            ->assertSee('My profile')
            ->assertSee('Notifications');

        foreach (['uk', 'de', 'es', 'pl'] as $locale) {
            $this->actingAs($admin, 'admin')
                ->from('/admin')
                ->get('/admin/locale/'.$locale)
                ->assertRedirect('/admin');

            $this->assertSame($locale, session('admin_locale'));
        }

        $this->actingAs($admin, 'admin')
            ->get('/admin/locale/fr')
            ->assertNotFound();
    }

    public function test_admin_agency_data_is_read_only(): void
    {
        $this->seed();

        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();
        $client = Client::query()->where('name', 'Nordic Atelier')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get('/admin/clients/create')
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->get('/admin/users/create')
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->get('/admin/assets/create')
            ->assertNotFound();

        $this->actingAs($admin, 'admin');
        Livewire::test(ListClients::class)
            ->assertActionDoesNotExist('create')
            ->assertTableActionVisible('view', $client)
            ->assertTableActionVisible('delete', $client)
            ->assertTableActionDoesNotExist('edit');

        Livewire::test(ListUsers::class)
            ->assertActionDoesNotExist('create');

        Livewire::test(ViewClient::class, ['record' => $client->id])
            ->assertOk()
            ->assertSee('Nordic Atelier')
            ->assertSee('nordic-atelier.ru');
    }
}
