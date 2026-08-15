<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HttpWebsite implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || self::normalize($value) === null) {
            $fail(__('validation.url', ['attribute' => $attribute]));
        }
    }

    public static function normalize(?string $value): ?string
    {
        $website = trim((string) $value);

        if ($website === '') {
            return null;
        }

        $scheme = parse_url($website, PHP_URL_SCHEME);

        if ($scheme !== null && ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return null;
        }

        $url = $scheme === null ? 'https://'.$website : $website;

        if (filter_var($url, FILTER_VALIDATE_URL) === false || parse_url($url, PHP_URL_HOST) === null) {
            return null;
        }

        return $url;
    }
}
