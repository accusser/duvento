<?php

namespace Tests\Feature;

use App\Install\EnvWriter;
use App\Install\InstallerState;
use App\Models\AdminUser;
use App\Models\Asset;
use App\Models\Client;
use App\Models\User;
use App\Support\AdminPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InstallerWizardTest extends TestCase
{
    use RefreshDatabase;

    private ?string $originalLock = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['duvento.test_installer' => true]);

        if (is_file(InstallerState::lockPath())) {
            $this->originalLock = (string) file_get_contents(InstallerState::lockPath());
            unlink(InstallerState::lockPath());
        }
    }

    protected function tearDown(): void
    {
        @unlink(InstallerState::lockPath());
        if ($this->originalLock !== null) {
            file_put_contents(InstallerState::lockPath(), $this->originalLock);
        }

        parent::tearDown();
    }

    public function test_fresh_application_opens_locale_step(): void
    {
        $this->get('/install')
            ->assertOk()
            ->assertSee('Добро пожаловать');
    }

    public function test_locale_advances_the_wizard(): void
    {
        $this->post('/install/locale', ['locale' => 'ru'])
            ->assertRedirect('/install');

        $this->get('/install')
            ->assertOk()
            ->assertSee('Проверка сервера');
    }

    public function test_locked_installer_returns_not_found(): void
    {
        InstallerState::lock('https://duvento.test', 'adm-locked');

        $this->get('/install')->assertNotFound();
    }

    public function test_finish_creates_only_admin_owner_and_system_catalog(): void
    {
        $env = Mockery::mock(EnvWriter::class);
        $env->shouldReceive('setMany')->once();
        $this->app->instance(EnvWriter::class, $env);

        $response = $this
            ->withSession([
                'install.step' => 'admin',
                'install.locale' => 'ru',
            ])
            ->post('/install/admin', [
                'name' => 'Owner',
                'email' => 'owner@example.test',
                'workspace' => 'Agency',
                'password' => 'StrongPassword123',
                'password_confirmation' => 'StrongPassword123',
                'admin_path' => 'adm-secret1',
            ]);

        $response->assertOk()->assertSee('Duvento установлен');
        $this->assertDatabaseCount((new AdminUser)->getTable(), 1);
        $this->assertDatabaseCount((new User)->getTable(), 1);
        $this->assertDatabaseCount((new Client)->getTable(), 0);
        $this->assertDatabaseCount((new Asset)->getTable(), 0);
        $this->assertTrue(InstallerState::isLocked());
    }

    public function test_generated_admin_path_is_valid_and_not_reserved(): void
    {
        $path = AdminPath::generate();

        $this->assertTrue(AdminPath::isValid($path));
        $this->assertNotContains($path, AdminPath::reserved());
    }
}
