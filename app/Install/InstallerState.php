<?php

namespace App\Install;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class InstallerState
{
    public const LOCK_FILE = 'installed';

    public const STEPS = [
        'locale',
        'environment',
        'database',
        'migrate',
        'admin',
        'done',
    ];

    public static function lockPath(): string
    {
        return storage_path('app/'.self::LOCK_FILE);
    }

    public static function hasLockFile(): bool
    {
        return is_file(self::lockPath());
    }

    public static function isLocked(): bool
    {
        return self::hasLockFile() || self::healFromDatabase();
    }

    /**
     * An instance restored from backup or a dev checkout can hold a working
     * database while storage/app/installed is missing. Existing accounts mean
     * the wizard must stay closed.
     */
    private static function healFromDatabase(): bool
    {
        // Mid-wizard the user may point at a reused database; only the lock file
        // may close an installer that is already running.
        if (self::isInstallerRequest()) {
            return false;
        }

        try {
            if (! Schema::hasTable('users')) {
                return false;
            }

            if (! DB::table('users')->exists() && ! DB::table('admin_users')->exists()) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        try {
            self::lock((string) config('app.url'), (string) config('duvento.admin_path', 'admin'));
        } catch (\Throwable) {
            // Read-only storage still counts as installed.
        }

        return true;
    }

    public static function lock(string $appUrl, string $adminPath): void
    {
        $content = json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_url' => $appUrl,
            'admin_path' => $adminPath,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (@file_put_contents(self::lockPath(), $content."\n", LOCK_EX) === false) {
            throw new \RuntimeException('Не удалось заблокировать установщик.');
        }
    }

    public static function isInstallerRequest(?Request $request = null): bool
    {
        $request ??= request();
        $path = trim($request->path(), '/');

        return $path === 'install' || str_starts_with($path, 'install/');
    }

    public static function step(Request $request): string
    {
        $step = (string) $request->session()->get('install.step', self::STEPS[0]);

        return in_array($step, self::STEPS, true) ? $step : self::STEPS[0];
    }

    public static function setStep(Request $request, string $step): void
    {
        if (! in_array($step, self::STEPS, true)) {
            throw new \InvalidArgumentException("Unknown installer step: {$step}");
        }

        $request->session()->put('install.step', $step);
    }
}
