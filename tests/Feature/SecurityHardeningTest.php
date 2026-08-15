<?php

namespace Tests\Feature;

use App\Enums\WorkspacePlan;
use App\Enums\WorkspaceRole;
use App\Livewire\Clients\Index;
use App\Livewire\Clients\Show;
use App\Livewire\Import\Index as ImportIndex;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Settings\Account;
use App\Livewire\Settings\Api;
use App\Livewire\Settings\Reminders;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AccountDeleter;
use App\Support\ActivityLogger;
use App\Support\CsvSafe;
use App\Support\PublicHttpUrl;
use App\Support\SslCertificateInspector;
use App\Support\WorkspaceProvisioner;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_uses_static_preview_without_database_assets(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('nordic-atelier.ru')
            ->assertSee('Demo');

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_foreign_workspace_id_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $victim = app(WorkspaceProvisioner::class)->create('Victim', $owner);

        $attacker = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Attacker', $attacker);
        $attacker->forceFill(['current_workspace_id' => $victim->id])->save();

        $this->actingAs($attacker)
            ->get(route('clients'))
            ->assertForbidden();
    }

    public function test_foreign_client_page_is_not_found(): void
    {
        $owner = User::factory()->create();
        $victim = app(WorkspaceProvisioner::class)->create('Victim', $owner);
        $client = $victim->clients()->create(['name' => 'Секрет']);

        $attacker = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Attacker', $attacker);

        $this->actingAs($attacker)
            ->get(route('clients.show', $client))
            ->assertNotFound();
    }

    public function test_foreign_report_page_is_not_found(): void
    {
        $owner = User::factory()->create();
        $victim = app(WorkspaceProvisioner::class)->create('Victim', $owner);
        $client = $victim->clients()->create(['name' => 'Секрет']);

        $attacker = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Attacker', $attacker);

        $this->actingAs($attacker)
            ->get(route('reports.show', $client))
            ->assertNotFound();
    }

    public function test_cannot_dismiss_foreign_notification(): void
    {
        $owner = User::factory()->create();
        $victim = app(WorkspaceProvisioner::class)->create('Victim', $owner);
        $log = ActivityLogger::log($victim, 'reminder.sent', null, ['name' => 'secret.notice']);

        $attacker = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Attacker', $attacker);
        $this->actingAs($attacker);

        Livewire::test(NotificationsIndex::class)->call('delete', $log->id);
        Livewire::test(NotificationsIndex::class)->call('clear');

        $this->assertNull($log->fresh()->dismissed_at);
    }

    public function test_foreign_asset_edit_page_is_not_found(): void
    {
        $owner = User::factory()->create();
        $victim = app(WorkspaceProvisioner::class)->create('Victim', $owner);
        $client = $victim->clients()->create(['name' => 'Секрет']);
        $type = AssetType::query()->where('key', 'domain')->first();
        $asset = Asset::query()->create([
            'workspace_id' => $victim->id,
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'secret.example',
        ]);

        $attacker = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Attacker', $attacker);

        $this->actingAs($attacker)
            ->get(route('assets.edit', $asset))
            ->assertNotFound();

        $this->actingAs($attacker)
            ->get(route('assets.show', $asset))
            ->assertNotFound();
    }

    public function test_csv_escapes_formula_cells(): void
    {
        $this->assertSame("'=1+1", CsvSafe::cell('=1+1'));

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Csv Studio', $user);
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $type = AssetType::query()->where('key', 'domain')->first();
        Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => '=CMD|\'/c calc\'!A0',
            'expires_at' => now()->addDays(10),
        ]);

        $csv = $this->actingAs($user)->get(route('export.assets'))->streamedContent();

        $this->assertStringContainsString("'=CMD", $csv);
        $this->assertStringNotContainsString("\n=CMD", $csv);
    }

    public function test_member_cannot_download_workspace_exports(): void
    {
        [, $member] = $this->ownerAndMember();

        $this->actingAs($member);

        $this->get(route('export.assets'))->assertForbidden();
        $this->get(route('export.clients'))->assertForbidden();
        $this->get(route('export.activity'))->assertForbidden();
    }

    public function test_client_website_rejects_non_http_schemes(): void
    {
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Safe Links', $user);
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Unsafe client')
            ->set('website', 'javascript:alert(document.cookie)')
            ->call('save')
            ->assertHasErrors(['website']);

        $this->assertNull((new Client(['website' => 'javascript:alert(1)']))->websiteHref());
        $this->assertSame('https://client.example', (new Client(['website' => 'client.example']))->websiteHref());
    }

    public function test_email_change_requires_current_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'before@example.test']);
        app(WorkspaceProvisioner::class)->create('Account Studio', $user);
        $this->actingAs($user);

        Livewire::test(Account::class)
            ->set('email', 'after@example.test')
            ->call('save')
            ->assertHasErrors(['currentPassword']);

        $this->assertSame('before@example.test', $user->fresh()->email);

        Livewire::test(Account::class)
            ->set('email', 'after@example.test')
            ->set('currentPassword', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('after@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_account_can_generate_a_confirmed_password(): void
    {
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Password Studio', $user);
        $this->actingAs($user);

        $this->get(route('settings.account'))
            ->assertOk()
            ->assertSee('Сгенерировать пароль')
            ->assertSee('Не меньше 8 символов');

        $component = Livewire::test(Account::class)
            ->call('generatePassword');

        $this->assertSame(16, strlen($component->get('password')));
        $this->assertSame($component->get('password'), $component->get('passwordConfirmation'));
    }

    public function test_ssl_inspector_skips_private_hosts(): void
    {
        $inspector = new SslCertificateInspector;

        $this->assertNull($inspector->expiryFor('127.0.0.1'));
        $this->assertNull($inspector->expiryFor('localhost'));
        $this->assertNull($inspector->expiryFor('10.0.0.5'));
        $this->assertFalse(PublicHttpUrl::allows('http://127.0.0.1/hook'));
        $this->assertFalse(PublicHttpUrl::allows('https://169.254.169.254/latest'));
        $this->assertTrue(PublicHttpUrl::allows('https://hooks.test/duvento'));
    }

    public function test_private_webhook_url_is_not_delivered(): void
    {
        $this->skipWithoutCloud();
        config(['edition.edition' => 'cloud']);
        Http::fake();

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Ssrf Studio', $user);
        $workspace->webhookEndpoints()->create([
            'url' => 'http://127.0.0.1/duvento',
            'secret' => 'secret',
            'events' => ['*'],
            'active' => true,
        ]);

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Hook Client')
            ->call('save');

        Http::assertNothingSent();
    }

    public function test_webhook_form_rejects_private_url(): void
    {
        $this->skipWithoutCloud();
        config(['edition.edition' => 'cloud']);

        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Api Studio', $user);
        $this->actingAs($user);

        Livewire::test(Api::class)
            ->set('webhookUrl', 'http://127.0.0.1/hook')
            ->call('saveWebhook')
            ->assertHasErrors(['webhookUrl']);
    }

    public function test_paddle_webhook_requires_fresh_signature(): void
    {
        $this->skipWithoutCloud();
        config(['edition.edition' => 'cloud']);

        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Sig Studio', $user);

        $payload = [
            'event_type' => 'subscription.activated',
            'data' => [
                'id' => 'sub_replay',
                'custom_data' => ['workspace_id' => $workspace->id, 'plan' => 'agency'],
            ],
        ];

        config(['paddle.webhook_secret' => '']);
        $this->postJson(route('billing.paddle.webhook'), $payload)->assertStatus(503);

        config(['paddle.webhook_secret' => 'whsec_test']);
        $this->postJson(route('billing.paddle.webhook'), $payload)->assertForbidden();

        $this->postPaddleWebhook($payload, time() - 1000)->assertForbidden();

        $this->postPaddleWebhook($payload)->assertOk();
        $this->assertSame(WorkspacePlan::Agency, $workspace->fresh()->plan);
    }

    public function test_member_cannot_delete_import_or_change_workspace_settings(): void
    {
        [$owner, $member, $workspace] = $this->ownerAndMember();
        $client = $workspace->clients()->create(['name' => 'Клиент']);
        $type = AssetType::query()->where('key', 'domain')->first();
        $asset = Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            'name' => 'keep.example',
            'expires_at' => now()->addDays(10),
        ]);

        $this->actingAs($member);

        Livewire::test(Index::class)->call('delete', $client->id)->assertForbidden();
        $this->assertDatabaseHas('clients', ['id' => $client->id]);

        Livewire::test(\App\Livewire\Assets\Index::class)->call('delete', $asset->id)->assertForbidden();
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);

        $log = ActivityLogger::log($workspace, 'asset.created', $asset, ['name' => $asset->name]);
        Livewire::test(\App\Livewire\Activity\Index::class)->call('clear')->assertForbidden();
        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);

        $this->get(route('import'))->assertForbidden();
        Livewire::test(ImportIndex::class)->assertForbidden();
        $this->assertDatabaseMissing('clients', ['name' => 'Hacker']);

        $this->get(route('settings.reminders'))->assertForbidden();
        Livewire::test(Reminders::class)->assertForbidden();

        Livewire::test(Account::class)
            ->set('workspaceCurrency', 'EUR')
            ->call('saveWorkspace')
            ->assertForbidden();
        $this->assertSame('USD', $workspace->fresh()->currency);

        $this->actingAs($owner);
        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Ещё клиент')
            ->call('save');
        Livewire::test(Index::class)->call('delete', $workspace->clients()->where('name', 'Ещё клиент')->value('id'));
        $this->assertDatabaseMissing('clients', ['name' => 'Ещё клиент']);
    }

    public function test_member_can_create_and_edit_clients(): void
    {
        [, $member] = $this->ownerAndMember();
        $this->actingAs($member);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Клиент члена')
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['name' => 'Клиент члена']);
    }

    public function test_only_owner_can_manage_public_client_link(): void
    {
        [$owner, $member, $workspace] = $this->ownerAndMember();
        $client = $workspace->clients()->create(['name' => 'Закрытый клиент']);

        $this->actingAs($member);

        Livewire::test(Show::class, ['client' => $client->id])
            ->assertDontSee(__('app.clients.share_title'))
            ->call('createPublicLink')
            ->assertForbidden();
        $this->assertNull($client->fresh()->public_token);

        $client->issuePublicToken();
        $token = $client->fresh()->public_token;

        Livewire::test(Show::class, ['client' => $client->id])
            ->assertDontSee($client->publicUrl())
            ->call('regeneratePublicLink')
            ->assertForbidden();
        $this->assertSame($token, $client->fresh()->public_token);

        Livewire::test(Show::class, ['client' => $client->id])
            ->call('disablePublicLink')
            ->assertForbidden();
        $this->assertSame($token, $client->fresh()->public_token);

        $this->actingAs($owner);
        Livewire::test(Show::class, ['client' => $client->id])
            ->call('disablePublicLink');
        $this->assertNull($client->fresh()->public_token);
    }

    public function test_admin_seeder_skips_production(): void
    {
        $this->app['env'] = 'production';
        (new AdminUserSeeder)->run();

        $this->assertDatabaseMissing('admin_users', ['email' => 'admin@duvento.local']);
    }

    public function test_smtp_test_does_not_leak_exception_message(): void
    {
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Mail Studio', $user);
        $this->actingAs($user);

        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('smtp://user:secret@127.0.0.1:587'));

        Livewire::test(Account::class)
            ->call('sendTestMail')
            ->assertHasErrors(['smtp'])
            ->assertSee('Не удалось отправить. Проверьте настройки почты.')
            ->assertDontSee('smtp://')
            ->assertDontSee('secret');
    }

    public function test_account_deleter_removes_owner_and_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Gone Studio', $user);
        $workspace->clients()->create(['name' => 'Клиент']);

        app(AccountDeleter::class)->delete($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    }

    public function test_owner_can_delete_account_with_password(): void
    {
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Gone Studio', $user);
        $workspace->clients()->create(['name' => 'Клиент']);
        $this->actingAs($user);

        $this->get(route('settings.account'))->assertOk()->assertSee('Удалить аккаунт');

        Livewire::test(Account::class)
            ->set('deletePassword', 'wrong')
            ->call('deleteAccount')
            ->assertHasErrors(['deletePassword']);
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        Livewire::test(Account::class)
            ->set('deletePassword', 'password')
            ->call('deleteAccount')
            ->assertRedirect(route('home'));

        $this->assertGuest();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
        $this->assertDatabaseMissing('clients', ['name' => 'Клиент']);
    }

    public function test_member_delete_keeps_workspace(): void
    {
        [$owner, $member, $workspace] = $this->ownerAndMember();
        $this->actingAs($member);

        Livewire::test(Account::class)
            ->set('deletePassword', 'password')
            ->call('deleteAccount')
            ->assertRedirect(route('home'));

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id]);
    }

    /**
     * @return array{0: User, 1: User, 2: Workspace}
     */
    private function ownerAndMember(): array
    {
        $owner = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Acl Studio', $owner);
        $member = User::factory()->create();
        $workspace->users()->attach($member->id, ['role' => WorkspaceRole::Member->value]);
        $member->forceFill(['current_workspace_id' => $workspace->id])->save();

        return [$owner->fresh(), $member->fresh(), $workspace];
    }
}
