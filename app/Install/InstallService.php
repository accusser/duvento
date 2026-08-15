<?php

namespace App\Install;

use App\Models\AdminUser;
use App\Models\User;
use App\Support\AdminPath;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

final class InstallService
{
    public function __construct(
        private readonly EnvWriter $env,
        private readonly WorkspaceProvisioner $workspaces,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function configureDatabase(array $data, string $appUrl): void
    {
        $connection = (string) $data['connection'];

        if ($connection === 'sqlite') {
            $database = database_path('database.sqlite');
            if (! is_file($database) && @touch($database) === false) {
                throw new \RuntimeException('Не удалось создать database/database.sqlite.');
            }

            $values = [
                'DB_CONNECTION' => 'sqlite',
                'DB_HOST' => null,
                'DB_PORT' => null,
                'DB_DATABASE' => $database,
                'DB_USERNAME' => null,
                'DB_PASSWORD' => null,
            ];
            config(['database.connections.sqlite.database' => $database]);
        } else {
            $values = [
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $data['host'],
                'DB_PORT' => $data['port'],
                'DB_DATABASE' => $data['database'],
                'DB_USERNAME' => $data['username'],
                'DB_PASSWORD' => $data['password'] ?? '',
            ];
            config([
                'database.connections.mysql.host' => $data['host'],
                'database.connections.mysql.port' => $data['port'],
                'database.connections.mysql.database' => $data['database'],
                'database.connections.mysql.username' => $data['username'],
                'database.connections.mysql.password' => $data['password'] ?? '',
            ]);
        }

        config(['database.default' => $connection]);
        DB::purge($connection);
        DB::connection($connection)->getPdo();

        $this->env->setMany($values + [
            'APP_ENV' => 'production',
            'APP_DEBUG' => true,
            'APP_URL' => $appUrl,
            'APP_EDITION' => 'self-host',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ]);
    }

    public function migrate(): void
    {
        $exitCode = Artisan::call('migrate', [
            '--force' => true,
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \RuntimeException(trim(Artisan::output()) ?: 'Миграции завершились с ошибкой.');
        }
    }

    /**
     * @param  array{name: string, email: string, password: string, workspace: string, admin_path: string, locale: string}  $data
     */
    public function finish(array $data, string $appUrl, bool $secure): void
    {
        if (! AdminPath::isValid($data['admin_path'])) {
            throw new \InvalidArgumentException('Недопустимый адрес админки.');
        }

        DB::transaction(function () use ($data): void {
            if (AdminUser::query()->exists() || User::query()->exists()) {
                throw new \RuntimeException('В базе уже есть пользователи. Для установки нужна чистая база.');
            }

            AdminUser::query()->create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
            ]);

            $owner = User::query()->create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
            ]);
            $owner->forceFill(['email_verified_at' => now()])->save();

            SystemCatalog::ensureAssetTypes();
            $this->workspaces->create($data['workspace'], $owner);
        });

        $this->env->setMany([
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
            'APP_URL' => $appUrl,
            'APP_LOCALE' => $data['locale'],
            'APP_FALLBACK_LOCALE' => 'en',
            'ADMIN_PATH' => $data['admin_path'],
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
            'SESSION_SECURE_COOKIE' => $secure,
        ]);

        config(['duvento.admin_path' => $data['admin_path']]);
        InstallerState::lock($appUrl, $data['admin_path']);
    }
}
