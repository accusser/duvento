<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use App\Notifications\AssetExpiringNotification;
use App\Support\SslCertificateInspector;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        Livewire::test(\App\Livewire\Settings\AssetTypes::class)
            ->set('label', 'Страховка')
            ->call('add');

        $custom = $user->currentWorkspace->assetTypes()->first();
        $clientId = $user->currentWorkspace->clients()->create(['name' => 'Клиент'])->id;

        Livewire::test(\App\Livewire\Assets\Index::class)
            ->call('create')
            ->set('formClientId', $clientId)
            ->set('assetTypeId', $custom->id)
            ->set('name', 'Полис 2026')
            ->set('expiresAt', now()->addDays(40)->toDateString())
            ->call('save');

        $this->assertDatabaseHas('assets', ['name' => 'Полис 2026', 'asset_type_id' => $custom->id]);
        $this->get(route('dashboard'))->assertSee('Полис 2026')->assertSee('OK');
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
    }

    public function test_schedule_covers_ssl_reminders_queue(): void
    {
        $commands = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? '')
            ->implode(' ');

        $this->assertStringContainsString('duvento:check-ssl', $commands);
        $this->assertStringContainsString('duvento:send-reminders', $commands);
        $this->assertStringContainsString('duvento:expire-trials', $commands);
        $this->assertStringContainsString('queue:work', $commands);
    }
}
