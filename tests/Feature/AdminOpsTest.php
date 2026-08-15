<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\WorkspaceRole;
use App\Filament\Pages\ExportData;
use App\Filament\Pages\InstanceHealth;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Widgets\StatsOverview;
use App\Livewire\Auth\Register;
use App\Livewire\Settings\Team;
use App\Models\ActivityLog;
use App\Models\AdminUser;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Support\HealthBannerDismissal;
use App\Support\Impersonation;
use App\Support\ScheduleHeartbeat;
use App\Support\SystemCatalog;
use App\Support\WorkspaceInviter;
use App\Support\WorkspaceProvisioner;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOpsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        $this->seed();

        return AdminUser::query()->where('email', 'admin@duvento.local')->firstOrFail();
    }

    public function test_admin_can_create_another_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $component = Livewire::test(CreateAdminUser::class)
            ->assertSee('Сгенерировать пароль')
            ->assertSee('Не меньше 8 символов')
            ->callAction(TestAction::make('generatePassword')->schemaComponent('password'));

        $this->assertSame(16, strlen($component->get('data.password')));
        $this->assertSame($component->get('data.password'), $component->get('data.password_confirmation'));

        $component
            ->fillForm([
                'name' => 'Второй',
                'email' => 'second@duvento.local',
                'phone' => '+7 900 000-00-00',
                'telegram' => '@second_admin',
                'password' => 'password12',
                'password_confirmation' => 'password12',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admin_users', [
            'email' => 'second@duvento.local',
            'phone' => '+7 900 000-00-00',
            'telegram' => 'second_admin',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'admin.created',
        ]);
    }

    public function test_admin_can_block_and_unblock_another_admin(): void
    {
        $admin = $this->admin();
        $other = AdminUser::query()->create([
            'name' => 'Второй',
            'email' => 'second@duvento.local',
            'password' => 'password12',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(ListAdminUsers::class)
            ->assertSee('Активен')
            ->assertDontSee('Телефон')
            ->assertDontSee('Telegram')
            ->callTableAction('block', $other)
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($other->refresh()->blocked_at);
        $this->assertFalse($other->canAccessPanel(filament()->getPanel('admin')));

        Livewire::test(ListAdminUsers::class)
            ->assertSee('Заблокирован')
            ->callTableAction('unblock', $other)
            ->assertHasNoTableActionErrors();

        $this->assertNull($other->refresh()->blocked_at);
    }

    public function test_make_admin_command_creates_user(): void
    {
        $this->artisan('duvento:make-admin', [
            'email' => 'ops@duvento.local',
            'name' => 'Ops',
            '--password' => 'secretpass',
        ])->assertSuccessful();

        $this->assertDatabaseHas('admin_users', ['email' => 'ops@duvento.local', 'name' => 'Ops']);
    }

    public function test_admin_can_impersonate_and_stop(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Алекс']);
        app(WorkspaceProvisioner::class)->create('Studio', $user);

        $this->actingAs($admin, 'admin');

        Livewire::test(ListUsers::class)
            ->callTableAction('impersonate', $user)
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(Impersonation::active());
        $this->assertAuthenticatedAs($user, 'web');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Вы вошли как Алекс')
            ->assertSee('Вернуться в админку');

        $this->post(route('impersonation.stop'))
            ->assertRedirect('/admin/users');

        $this->assertGuest('web');
        $this->assertFalse(Impersonation::active());
    }

    public function test_instance_health_page(): void
    {
        ScheduleHeartbeat::touch('scheduler');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/instance-health')
            ->assertOk()
            ->assertSee('Здоровье инстанса')
            ->assertSee('Планировщик')
            ->assertSee('SSL-джоба')
            ->assertSee('php artisan schedule:run')
            ->assertSee('Почта');

        $this->actingAs($admin, 'admin');
        Livewire::test(InstanceHealth::class)->assertOk();
    }

    public function test_admin_can_save_mail_settings(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(InstanceHealth::class)
            ->set('mailer', 'smtp')
            ->set('host', 'smtp.example.com')
            ->set('port', '587')
            ->set('username', 'ops')
            ->set('password', 'secret')
            ->set('scheme', 'tls')
            ->set('from_address', 'ops@duvento.test')
            ->set('from_name', 'Duvento')
            ->call('saveMail')
            ->assertHasNoErrors()
            ->assertSet('password', '');

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('ops@duvento.test', config('mail.from.address'));
        $this->assertSame('secret', config('mail.mailers.smtp.password'));

        Livewire::test(InstanceHealth::class)
            ->set('mailer', 'smtp')
            ->set('host', 'smtp.example.com')
            ->set('port', '465')
            ->set('username', 'ops')
            ->set('password', '')
            ->set('scheme', 'ssl')
            ->set('from_address', 'ops@duvento.test')
            ->set('from_name', 'Duvento')
            ->call('saveMail')
            ->assertHasNoErrors();

        $this->assertSame(465, (int) config('mail.mailers.smtp.port'));
        $this->assertSame('secret', config('mail.mailers.smtp.password'));
    }

    public function test_mail_settings_migrate_missing_table(): void
    {
        Schema::dropIfExists('instance_settings');
        DB::table('migrations')
            ->where('migration', '2026_08_15_180000_create_instance_settings_table')
            ->delete();

        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(InstanceHealth::class)
            ->set('mailer', 'log')
            ->set('from_address', 'ops@duvento.test')
            ->set('from_name', 'Duvento')
            ->call('saveMail')
            ->assertHasNoErrors();

        $this->assertTrue(Schema::hasTable('instance_settings'));
        $this->assertSame('log', config('mail.default'));
    }

    public function test_admin_can_choose_and_export_data(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Экспортируемый пользователь']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/export-data')
            ->assertOk()
            ->assertSee('Что экспортировать?')
            ->assertSee('Пользователи');

        $this->actingAs($admin, 'admin');
        Livewire::test(ExportData::class)
            ->set('dataset', 'users')
            ->call('export')
            ->assertFileDownloaded('duvento-users-'.now()->format('Y-m-d').'.csv');
    }

    public function test_activity_log_has_view_and_filters(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $user);
        $log = ActivityLog::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'action' => 'reminder.sent',
            'properties' => ['name' => 'example.com'],
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertSee('Напоминание отправлено')
            ->assertSee('example.com');

        $this->actingAs($admin, 'admin');
        Livewire::test(ViewActivityLog::class, ['record' => $log->id])
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('Studio');

        $adminLog = ActivityLog::query()->create([
            'workspace_id' => $workspace->id,
            'admin_user_id' => $admin->id,
            'action' => 'admin.updated',
            'properties' => [
                'model' => 'Ticket',
                'record_id' => 2,
                'name' => 'Доброго дня',
                'fields' => ['last_message_at'],
            ],
        ]);

        Livewire::test(ViewActivityLog::class, ['record' => $adminLog->id])
            ->assertOk()
            ->assertSee('Тикет')
            ->assertSee('Доброго дня')
            ->assertSee('Последнее сообщение')
            ->assertDontSee('"model"');

        Livewire::test(ListActivityLogs::class)
            ->assertSee('Тикет · Доброго дня')
            ->assertDontSee('Объект: Тикет');
    }

    public function test_admin_can_clear_activity_log(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $user);
        ActivityLog::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'action' => 'asset.created',
            'properties' => ['name' => 'clear.me'],
        ]);

        $this->assertGreaterThan(0, ActivityLog::query()->count());

        $this->actingAs($admin, 'admin');
        Livewire::test(ListActivityLogs::class)
            ->callAction('clear')
            ->assertHasNoActionErrors();

        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_admin_search_and_notifications(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Искатель']);
        $workspace = app(WorkspaceProvisioner::class)->create('ПоискСтудия', $user);
        ActivityLog::query()->create([
            'workspace_id' => $workspace->id,
            'action' => 'ssl.check_failed',
            'properties' => ['name' => 'fail.example'],
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/search?q=Поиск')
            ->assertOk()
            ->assertSee('ПоискСтудия');

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('fail.example')
            ->assertSee('action="'.url('/admin/search').'"', false);
    }

    public function test_admin_dashboard_shows_latest_users(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Новый Юзер', 'email' => 'new@studio.test']);

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Пользователи')
            ->assertSee('Новый Юзер');
    }

    public function test_admin_can_hide_health_banner_until_problems_change(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(StatsOverview::class)
            ->assertSee(__('admin.health.banner'))
            ->call('dismissHealthAlerts')
            ->assertDontSee(__('admin.health.banner'));

        Livewire::test(StatsOverview::class)->assertDontSee(__('admin.health.banner'));

        $this->assertSame(
            [['key' => 'smtp']],
            HealthBannerDismissal::visible($admin->id, [['key' => 'smtp']]),
        );
    }

    public function test_user_memberships_are_visible_on_view_page(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $owner);
        $member = User::factory()->create();
        $member->workspaces()->attach($workspace->id, ['role' => WorkspaceRole::Member->value]);

        $this->actingAs($admin, 'admin');

        Livewire::test(ViewUser::class, ['record' => $member->id])
            ->assertOk()
            ->assertSee('Studio')
            ->assertSee(__('app.enums.role.member'));
    }

    public function test_owner_can_invite_and_new_user_accepts(): void
    {
        Notification::fake();
        SystemCatalog::ensureAssetTypes();
        $owner = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $owner);
        $this->actingAs($owner);

        Livewire::test(Team::class)
            ->set('email', 'mate@studio.test')
            ->set('role', WorkspaceRole::Member->value)
            ->call('invite')
            ->assertHasNoErrors();

        $invite = WorkspaceInvitation::query()->where('email', 'mate@studio.test')->first();
        $this->assertNotNull($invite);

        $this->post(route('logout'));

        Livewire::test(Register::class)
            ->set('inviteToken', $invite->token)
            ->set('email', 'mate@studio.test')
            ->set('name', 'Напарник')
            ->set('password', 'password12')
            ->set('password_confirmation', 'password12')
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $mate = User::query()->where('email', 'mate@studio.test')->first();
        $this->assertNotNull($mate);
        $this->assertTrue($mate->workspaces()->whereKey($workspace->id)->exists());
        $this->assertSame(WorkspaceRole::Member, $mate->roleIn($workspace->fresh()));
    }

    public function test_inviter_rejects_existing_member(): void
    {
        $owner = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $owner);

        $this->expectException(ValidationException::class);
        app(WorkspaceInviter::class)->invite($workspace, $owner->email, WorkspaceRole::Member);
    }

    public function test_assets_export_action_exists(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(ListAssets::class)
            ->assertActionExists('export');

        Livewire::test(ListUsers::class)
            ->assertActionExists('export');
    }

    public function test_activity_log_lists_workspace_events_from_client_panel(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(ListActivityLogs::class)
            ->assertSee('Напоминание отправлено')
            ->assertSee('nordic-atelier.ru')
            ->assertSee('Северная студия');
    }

    public function test_admin_filters_use_select_options(): void
    {
        $admin = $this->admin();
        $pixel = Workspace::query()->where('name', 'Pixel & Co')->firstOrFail();
        $sslType = AssetType::query()->whereNull('workspace_id')->where('key', 'ssl')->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(ListClients::class)
            ->assertSee('Nordic Atelier')
            ->filterTable('workspace_id', $pixel->id)
            ->assertDontSee('Nordic Atelier')
            ->assertSee('Harbor Books');

        Livewire::test(ListAssets::class)
            ->filterTable('asset_type_id', $sslType->id)
            ->assertSee('nordic-atelier.ru')
            ->assertDontSee('Timeweb VPS')
            ->filterTable('status', 'critical')
            ->assertSee('nordic-atelier.ru');
    }

    public function test_admin_can_view_asset_history(): void
    {
        $admin = $this->admin();
        $asset = Asset::query()
            ->where('name', 'nordic-atelier.ru')
            ->whereHas('assetType', fn ($query) => $query->where('key', 'ssl'))
            ->firstOrFail();

        $this->actingAs($admin, 'admin');
        Livewire::test(ViewAsset::class, ['record' => $asset->id])
            ->assertOk()
            ->assertSee('Nordic Atelier')
            ->assertSee('Напоминание отправлено');
    }

    public function test_admin_dashboard_shows_health_alert_and_ticket_stats(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Support Studio', $user);

        Ticket::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'subject' => 'Нужна помощь',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Normal,
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Планировщик')
            ->assertSee('Давно не было')
            ->assertSee('Открытые тикеты')
            ->assertSee('Новые за 7 дней');
    }
}
