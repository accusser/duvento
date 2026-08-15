<?php

namespace App\Install;

final class InstallBootstrap
{
    public static function beforeHttp(): void
    {
        if (InstallerState::hasLockFile() || ! InstallerState::isInstallerRequest()) {
            return;
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
        ]);

        if (! blank(config('app.key'))) {
            return;
        }

        try {
            $key = (new EnvWriter)->generateAppKeyIfEmpty();
            config(['app.key' => $key]);
        } catch (\Throwable) {
            // The environment step reports write errors to the user.
        }
    }
}
