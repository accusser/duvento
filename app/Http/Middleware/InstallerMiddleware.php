<?php

namespace App\Http\Middleware;

use App\Install\InstallerState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InstallerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $testing = app()->runningUnitTests()
            || ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null) === 'testing';

        if ($testing && ! config('duvento.test_installer', false)) {
            return $next($request);
        }

        if (InstallerState::isInstallerRequest($request) && InstallerState::isLocked()) {
            abort(404);
        }

        if (! InstallerState::isLocked()
            && ! InstallerState::isInstallerRequest($request)
            && ! $request->is('up')) {
            return redirect()->route('install.index');
        }

        if (! InstallerState::isLocked() && ! $testing) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
                'queue.default' => 'sync',
            ]);
        }

        return $next($request);
    }
}
