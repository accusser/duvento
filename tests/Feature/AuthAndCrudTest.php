<?php

namespace Tests\Feature;

use App\Livewire\Assets\Form;
use App\Livewire\Auth\Register;
use App\Livewire\Clients\Index;
use App\Livewire\Settings\Account;
use App\Models\AssetType;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class AuthAndCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_see_dashboard(): void
    {
        Notification::fake();

        Livewire::test(Register::class)
            ->set('name', 'Иван')
            ->set('email', 'ivan@agency.test')
            ->set('workspace', 'Иван Студия')
            ->set('password', 'password12')
            ->set('password_confirmation', 'password12')
            ->call('register')
            ->assertRedirect(route('dashboard'));

        Notification::assertSentTo(
            User::query()->where('email', 'ivan@agency.test')->first(),
            VerifyEmailNotification::class,
        );

        $this->assertDatabaseHas('users', ['email' => 'ivan@agency.test']);
        $this->assertDatabaseHas('workspaces', ['name' => 'Иван Студия']);
        $this->assertNull(User::query()->where('email', 'ivan@agency.test')->value('email_verified_at'));
    }

    public function test_email_verification_status_and_signed_link(): void
    {
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Studio', $user);
        $this->actingAs($user);

        $this->get(route('settings.account'))
            ->assertOk()
            ->assertSee(__('app.account.verified'));

        $user->forceFill(['email_verified_at' => null])->save();

        $this->get(route('settings.account'))
            ->assertOk()
            ->assertSee(__('app.account.unverified'))
            ->assertSee(__('app.account.verify_send'));

        Notification::fake();
        Livewire::test(Account::class)->call('sendVerification');
        Notification::assertSentTo($user->fresh(), VerifyEmailNotification::class);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect(route('settings.account'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_client_and_asset_appear_on_dashboard(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Studio', $user);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'Клиент А')
            ->set('contactName', 'Иван Петров')
            ->set('email', 'a@client.test')
            ->set('website', 'client-a.test')
            ->call('save')
            ->assertRedirect(route('clients.show', $user->currentWorkspace->clients()->first()));

        $this->assertDatabaseHas('clients', [
            'name' => 'Клиент А',
            'contact_name' => 'Иван Петров',
            'website' => 'client-a.test',
        ]);

        $client = $user->currentWorkspace->clients()->first();
        $ssl = AssetType::query()->where('key', 'ssl')->first();

        $this->get(route('assets.create', ['client_id' => $client->id]))
            ->assertOk()
            ->assertSee('Новый актив')
            ->assertSee('Клиент А');

        $component = Livewire::test(Form::class)
            ->set('formClientId', $client->id)
            ->set('assetTypeId', $ssl->id)
            ->set('name', 'example.com')
            ->set('expiresAt', now()->addDays(5)->toDateString())
            ->set('owner', 'agency')
            ->set('payer', 'agency')
            ->call('save');

        $asset = $user->currentWorkspace->assets()->first();
        $this->assertNotNull($asset);
        $component->assertRedirect(route('assets.show', $asset));

        $this->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('Клиент А');

        $this->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Клиент А')
            ->assertSee('Иван Петров')
            ->assertSee('example.com');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('Критично');
    }

    public function test_csv_export_downloads(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'alex@severnaya.example')->first();

        $response = $this->actingAs($user)->get(route('export.assets'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('nordic-atelier.ru', $response->streamedContent());
    }
}
