<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Index;
use App\Models\AssetType;
use App\Models\User;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_header_lists_supported_languages(): void
    {
        $user = $this->userWithWorkspace();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Язык')
            ->assertSee('Русский')
            ->assertSee('Українська')
            ->assertSee('Deutsch')
            ->assertSee('Español')
            ->assertSee('Polski')
            ->assertSee('English');
    }

    public function test_user_can_switch_locale(): void
    {
        $user = $this->userWithWorkspace();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('locale', 'de'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ruhiger Überblick über alle Fristen')
            ->assertSee('Sprache')
            ->assertDontSee('Спокойный обзор сроков');
    }

    public function test_unknown_locale_is_not_found(): void
    {
        $user = $this->userWithWorkspace();

        $this->actingAs($user)
            ->get('/locale/fr')
            ->assertNotFound();
    }

    public function test_system_asset_types_follow_locale(): void
    {
        $user = $this->userWithWorkspace();

        $this->actingAs($user)
            ->get(route('settings.types'))
            ->assertOk()
            ->assertSee('SSL-сертификат')
            ->assertSee('Домен')
            ->assertSee('Лицензия плагина')
            ->assertSee('Другое');

        $this->actingAs($user)
            ->from(route('settings.types'))
            ->get(route('locale', 'de'))
            ->assertRedirect(route('settings.types'));

        $this->actingAs($user)
            ->get(route('settings.types'))
            ->assertOk()
            ->assertSee('SSL-Zertifikat')
            ->assertSee('Plugin-Lizenz')
            ->assertSee('Sonstiges')
            ->assertDontSee('SSL-сертификат')
            ->assertDontSee('Лицензия плагина');
    }

    public function test_file_picker_follows_locale(): void
    {
        $user = $this->userWithWorkspace();

        $this->actingAs($user)
            ->get(route('import'))
            ->assertOk()
            ->assertSee('Выберите файл')
            ->assertSee('Файл не выбран');

        $this->actingAs($user)
            ->from(route('import'))
            ->get(route('locale', 'de'))
            ->assertRedirect(route('import'));

        $this->actingAs($user)
            ->get(route('import'))
            ->assertOk()
            ->assertSee('Datei auswählen')
            ->assertSee('Keine Datei ausgewählt')
            ->assertDontSee('Выберите файл');
    }

    public function test_validation_follows_locale(): void
    {
        $user = $this->userWithWorkspace();
        $this->actingAs($user);
        app()->setLocale('de');

        Livewire::test(Index::class)
            ->call('saveQuickClient')
            ->assertHasErrors(['quickClientName'])
            ->assertSee('Das Feld Name ist erforderlich.')
            ->assertDontSee('The name field is required.');
    }

    public function test_quick_add_and_delete_dispatch_translated_toasts(): void
    {
        $user = $this->userWithWorkspace();
        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="ny-toasts"', false)
            ->assertSee('bottom-0 end-0', false);

        Livewire::test(Index::class)
            ->set('quickClientName', 'Тест')
            ->call('saveQuickClient')
            ->assertDispatched('toast', function (string $event, array $params) {
                return $event === 'toast'
                    && ($params['message'] ?? null) === 'Клиент добавлен.'
                    && ($params['type'] ?? null) === 'success';
            });

        $client = $user->currentWorkspace->clients()->first();
        $typeId = AssetType::query()->where('key', 'ssl')->value('id');

        Livewire::test(Index::class)
            ->set('quickAssetClientId', $client->id)
            ->set('quickAssetTypeId', $typeId)
            ->set('quickAssetName', 'example.com')
            ->call('saveQuickAsset')
            ->assertDispatched('toast', function (string $event, array $params) {
                return ($params['message'] ?? null) === 'Актив добавлен.';
            });

        $asset = $user->currentWorkspace->assets()->first();

        Livewire::test(\App\Livewire\Assets\Index::class)
            ->call('delete', $asset->id)
            ->assertDispatched('toast', function (string $event, array $params) {
                return ($params['message'] ?? null) === 'Актив удалён.'
                    && ($params['type'] ?? null) === 'delete';
            });

        Livewire::test(\App\Livewire\Clients\Index::class)
            ->call('delete', $client->id)
            ->assertDispatched('toast', function (string $event, array $params) {
                return ($params['message'] ?? null) === 'Клиент удалён.'
                    && ($params['type'] ?? null) === 'delete';
            });

        app()->setLocale('de');

        Livewire::test(Index::class)
            ->set('quickClientName', 'Kunde')
            ->call('saveQuickClient')
            ->assertDispatched('toast', function (string $event, array $params) {
                return ($params['message'] ?? null) === 'Kunde hinzugefügt.';
            });
    }

    private function userWithWorkspace(): User
    {
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Studio', $user);

        return $user;
    }
}
