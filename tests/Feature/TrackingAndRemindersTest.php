<?php

namespace Tests\Feature;

use App\Livewire\Assets\Form;
use App\Livewire\Assets\Index;
use App\Livewire\Settings\AssetTypes;
use App\Livewire\Settings\Reminders;
use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use App\Notifications\AssetExpiringNotification;
use App\Support\SslCertificateInspector;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TrackingAndRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_asset_type_works_like_system(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Studio', $user);
        $this->actingAs($user);

        Livewire::test(AssetTypes::class)
            ->set('label', 'Страховка')
            ->call('add');

        $custom = $user->currentWorkspace->assetTypes()->first();
        $clientId = $user->currentWorkspace->clients()->create(['name' => 'Клиент'])->id;

        Livewire::test(Form::class)
            ->set('formClientId', $clientId)
            ->set('assetTypeId', $custom->id)
            ->set('name', 'Полис 2026')
            ->set('expiresAt', now()->addDays(40)->toDateString())
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('assets', ['name' => 'Полис 2026', 'asset_type_id' => $custom->id]);
        $this->get(route('dashboard'))->assertSee('Полис 2026')->assertSee('В порядке');
    }

    public function test_ssl_command_updates_expiry(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $user);
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $ssl = AssetType::query()->where('key', 'ssl')->first();
        $asset = Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'asset_type_id' => $ssl->id,
            'name' => 'example.com',
            'expires_at' => now()->addDays(10),
            'ssl_check_enabled' => true,
        ]);

        $this->mock(SslCertificateInspector::class, function ($mock) {
            $mock->shouldReceive('expiryFor')->once()->with('example.com')->andReturn(Carbon::parse('2030-01-15'));
        });

        $this->artisan('duvento:check-ssl')->assertSuccessful();

        $this->assertSame('2030-01-15', $asset->fresh()->expires_at->toDateString());
        $this->assertDatabaseHas('activity_logs', ['action' => 'ssl.updated']);
    }

    public function test_reminder_is_sent_seven_days_before(): void
    {
        Notification::fake();
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create(['email' => 'owner@studio.test']);
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $user);
        $client = $workspace->clients()->create(['name' => 'Клиент', 'email' => 'client@test.com']);
        $type = AssetType::query()->where('key', 'domain')->first();

        Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'soon.test',
            'expires_at' => now()->addDays(7),
        ]);

        $this->artisan('duvento:send-reminders')->assertSuccessful();

        Notification::assertSentOnDemand(AssetExpiringNotification::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'reminder.sent']);
        $this->assertDatabaseHas('reminder_dispatches', ['days_before' => 7]);

        $this->artisan('duvento:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('reminder_dispatches', 1);
        $this->assertSame(1, ActivityLog::query()->where('action', 'reminder.sent')->count());
    }

    public function test_renew_from_list_writes_renewed_not_updated(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $user);
        $this->actingAs($user);
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $type = AssetType::query()->where('key', 'domain')->first();
        $asset = Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'renew.test',
            'expires_at' => now()->addDays(5),
        ]);

        $newDate = now()->addYear()->toDateString();

        Livewire::test(Index::class)
            ->call('beginRenew', $asset->id)
            ->set('renewDate', $newDate)
            ->call('confirmRenew');

        $this->assertSame($newDate, $asset->fresh()->expires_at->toDateString());
        $this->assertDatabaseHas('activity_logs', ['action' => 'asset.renewed', 'subject_id' => $asset->id]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'asset.updated', 'subject_id' => $asset->id]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'asset.date_changed', 'subject_id' => $asset->id]);
    }

    public function test_activity_page_shows_reminder_and_renewal(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'alex@severnaya.example')->first();

        $this->actingAs($user)
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('Напоминание отправлено')
            ->assertSee('Отмечено продление')
            ->assertSee('nordic-atelier.ru');

        Livewire::test(\App\Livewire\Activity\Index::class)->call('clear');
        $this->assertSame(0, $user->currentWorkspace->activityLogs()->count());
        $this->get(route('activity'))->assertSee(__('app.activity.empty'));
    }

    public function test_reminder_settings_and_custom_type_are_reachable(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'alex@severnaya.example')->first();

        $this->actingAs($user)->get(route('settings.reminders'))
            ->assertOk()
            ->assertSee('30')
            ->assertSee('Правила воркспейса')
            ->assertSee('@BotFather')
            ->assertSee('/newbot');

        $this->actingAs($user)->get(route('settings.types'))
            ->assertOk()
            ->assertSee('Страховка');

        $this->actingAs($user)->get(route('assets'))
            ->assertOk()
            ->assertSee('Продлить')
            ->assertSee('Продлевает')
            ->assertSee('Платит');
    }

    public function test_schedule_covers_ssl_reminders_queue(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? '')
            ->implode(' ');

        $this->assertStringContainsString('duvento:check-ssl', $commands);
        $this->assertStringContainsString('duvento:send-reminders', $commands);
        $this->assertStringContainsString('duvento:expire-trials', $commands);
        $this->assertStringContainsString('queue:work', $commands);
    }

    public function test_telegram_can_be_connected_and_used_for_reminders(): void
    {
        $this->fakeTelegramHttp();
        Notification::fake();
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create(['email' => 'owner@studio.test']);
        $workspace = app(WorkspaceProvisioner::class)->create('Studio', $user);
        $this->actingAs($user);

        $token = '123456:AATestTokenTestTokenTestToken12';

        Livewire::test(Reminders::class)
            ->set('telegramToken', $token)
            ->call('findTelegramChats')
            ->assertSet('telegramChatId', '999')
            ->call('connectTelegram')
            ->assertHasNoErrors();

        $workspace = $workspace->fresh();
        $this->assertTrue($workspace->telegramConnected());
        $this->assertSame('duvento_bot', $workspace->telegram_bot_username);
        $this->assertSame('999', $workspace->telegram_chat_id);

        $client = $workspace->clients()->create(['name' => 'Клиент', 'email' => 'client@test.com']);
        $type = AssetType::query()->where('key', 'domain')->first();
        Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'soon.test',
            'expires_at' => now()->addDays(7),
        ]);

        $this->artisan('duvento:send-reminders')->assertSuccessful();

        Notification::assertSentOnDemand(AssetExpiringNotification::class);
        $this->assertDatabaseHas('reminder_dispatches', ['days_before' => 7, 'channel' => 'email']);
        $this->assertDatabaseHas('reminder_dispatches', ['days_before' => 7, 'channel' => 'telegram']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '999'
            && str_contains((string) $request['text'], 'soon.test'));

        Livewire::test(Reminders::class)
            ->call('disconnectTelegram')
            ->assertHasNoErrors();

        $this->assertFalse($workspace->fresh()->telegramConnected());
    }

    private function fakeTelegramHttp(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'getMe')) {
                return Http::response(['ok' => true, 'result' => ['id' => 1, 'username' => 'duvento_bot']]);
            }

            if (str_contains($url, 'getChat')) {
                return Http::response(['ok' => true, 'result' => ['id' => 999, 'type' => 'private', 'first_name' => 'Иван']]);
            }

            if (str_contains($url, 'getUpdates')) {
                return Http::response(['ok' => true, 'result' => [
                    ['update_id' => 1, 'message' => ['chat' => ['id' => 999, 'type' => 'private', 'first_name' => 'Иван']]],
                ]]);
            }

            if (str_contains($url, 'sendMessage')) {
                return Http::response(['ok' => true, 'result' => ['message_id' => 1]]);
            }

            return Http::response(['ok' => false, 'description' => 'unknown'], 404);
        });
    }
}
