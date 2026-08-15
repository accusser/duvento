<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace;

        if ($workspace === null) {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => __('app.auth.workspace_missing'),
            ]);
        }

        if (! $user->workspaces()->whereKey($workspace->id)->exists()) {
            abort(403);
        }

        if ($workspace->blocked_at !== null) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('app.auth.workspace_blocked'),
            ]);
        }

        return $next($request);
    }
}
