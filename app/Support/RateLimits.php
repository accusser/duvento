<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class RateLimits
{
    public static function hitOrFail(string $key, int $max = 5, int $decaySeconds = 60, string $field = 'email'): void
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages([
                $field => __('app.auth.throttled'),
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
