<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Export\Index as ExportIndex;
use App\Livewire\Import\Index as ImportIndex;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Settings\Account;
use App\Models\ActivityLog;
use App\Models\AdminUser;
use App\Models\AssetType;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AgencyWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Studio', $user);

        return $user->fresh();
    }

    public function test_dashboard_shows_onboarding_and_quick_add(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Настройка воркспейса')
            ->assertSee('0 из 6 шагов выполнено')
            ->assertSee('Добавьте первого клиента')
            ->assertSee('Импорт CSV')
            ->assertSee('Быстрый клиент');

        Livewire::test(DashboardIndex::class)
            ->call('markStepDone', 'client')
            ->assertSee('1 из 6 шагов выполнено');

        Livewire::test(DashboardIndex::class)
            ->set('quickClientName', 'Клиент Б')
            ->set('quickClientEmail', 'b@client.test')
            ->call('saveQuickClient');

        $this->assertDatabaseHas('clients', ['name' => 'Клиент Б']);
    }

    public function test_quick_client_validation_uses_lexicon(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(DashboardIndex::class)
            ->call('saveQuickClient')
            ->assertHasErrors(['quickClientName' => 'required'])
            ->assertSee('Поле Имя обязательно.')
            ->assertDontSee('The quick client name field is required.');

        app()->setLocale('en');

        Livewire::test(DashboardIndex::class)
            ->call('saveQuickClient')
            ->assertHasErrors(['quickClientName' => 'required'])
            ->assertSee('The name field is required.')
            ->assertDontSee('The quick client name field is required.');
    }

    public function test_onboarding_open_shows_create_form(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(DashboardIndex::class)
            ->call('openStep', 'client')
            ->assertSet('showClientForm', true)
            ->assertSee('Новый клиент')
            ->assertDontSee(route('clients', ['create' => 1]), false);

        Livewire::test(DashboardIndex::class)
            ->call('openStep', 'asset')
            ->assertRedirect(route('assets.create'));
    }

    public function test_csv_import_creates_clients_and_assets(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $workspace = $user->currentWorkspace;
        $workspace->clients()->create(['name' => 'Acme']);

        $clientsCsv = UploadedFile::fake()->createWithContent('clients.csv', "name,email,notes\nBeta,beta@test.com,hi\n");

        Livewire::test(ImportIndex::class)
            ->set('target', 'clients')
            ->set('file', $clientsCsv)
            ->call('import');

        $this->assertDatabaseHas('clients', ['name' => 'Beta', 'email' => 'beta@test.com']);

        $assetsCsv = UploadedFile::fake()->createWithContent(
            'assets.csv',
            "name,type,client,expires_at\nbeta.com,domain,Beta,".now()->addDays(20)->toDateString()."\n",
        );

        Livewire::test(ImportIndex::class)
            ->set('target', 'assets')
            ->set('file', $assetsCsv)
            ->call('import');

        $this->assertDatabaseHas('assets', ['name' => 'beta.com']);
    }

    public function test_reports_notifications_settings_and_exports(): void
    {
        $user = $this->user();
        $workspace = $user->currentWorkspace;
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $type = AssetType::query()->where('key', 'ssl')->first();
        $asset = $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'ssl.example',
            'expires_at' => now()->addDays(3),
        ]);
        ActivityLogger::log($workspace, 'reminder.sent', $asset, ['name' => $asset->name, 'email' => 'a@test.com']);

        $this->actingAs($user);

        $this->get(route('reports'))->assertOk()->assertSee('Клиент')
            ->assertSee('Экспорт клиентов')
            ->assertSee('Экспорт активов')
            ->assertSee('Экспорт журнала');
        $this->get(route('export'))
            ->assertOk()
            ->assertSee('Что экспортировать?')
            ->assertSee('Скачать CSV');
        Livewire::test(ExportIndex::class)
            ->set('dataset', 'assets')
            ->call('download')
            ->assertRedirect(route('export.assets'));
        $this->get(route('reports.show', $client))->assertOk()->assertSee('ssl.example')->assertSee('Продлите');
        $this->get(route('notifications'))->assertOk()->assertSee('Напоминание отправлено');
        $this->get(route('settings'))->assertRedirect(route('settings.account'));
        $this->get(route('settings.account'))->assertOk()->assertSee('Тест SMTP');

        $clientsCsv = $this->get(route('export.clients'));
        $clientsCsv->assertOk();
        $this->assertStringContainsString('Клиент', $clientsCsv->streamedContent());

        $activityCsv = $this->get(route('export.activity'));
        $activityCsv->assertOk();
        $this->assertStringContainsString('reminder.sent', $activityCsv->streamedContent());

        Livewire::test(NotificationsIndex::class)->call('markAll');
        $log = ActivityLog::query()->where('action', 'reminder.sent')->first();
        $this->assertNotNull($log?->read_at);

        Livewire::test(NotificationsIndex::class)->call('delete', $log->id);
        $this->assertNotNull($log->fresh()->dismissed_at);
        $this->get(route('notifications'))->assertDontSee('ssl.example');
        $this->get(route('activity'))->assertSee('ssl.example');

        Livewire::test(Account::class)
            ->set('name', 'Новое имя')
            ->call('save');

        $this->assertSame('Новое имя', $user->fresh()->name);

        Mail::fake();
        Livewire::test(Account::class)->call('sendTestMail')->assertHasNoErrors();
    }

    public function test_notifications_clear_keeps_activity_log(): void
    {
        $user = $this->user();
        $workspace = $user->currentWorkspace;
        ActivityLogger::log($workspace, 'reminder.sent', null, ['name' => 'one.test']);
        ActivityLogger::log($workspace, 'ssl.check_failed', null, ['name' => 'two.test']);
        $this->actingAs($user);

        Livewire::test(NotificationsIndex::class)->call('clear');

        $this->assertSame(2, ActivityLog::query()->whereNotNull('dismissed_at')->count());
        $this->get(route('notifications'))->assertDontSee('one.test')->assertSee(__('app.notifications.empty'));
        $this->get(route('activity'))->assertSee('one.test')->assertSee('two.test');
    }

    public function test_admin_sees_users_clients_and_assets_in_both_editions(): void
    {
        $this->seed();
        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Клиенты')
            ->assertSee('Активы')
            ->assertSee('Пользователи');

        $this->actingAs($admin, 'admin')->get('/admin/users')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/clients')->assertOk()->assertSee('Северная студия');
        $this->actingAs($admin, 'admin')->get('/admin/assets')->assertOk();
    }
}
