<?php

namespace App\Support;

use Illuminate\Support\Str;

final class AdminPath
{
    private const RESERVED = [
        'api', 'assets', 'dashboard', 'install', 'livewire', 'login',
        'logout', 'register', 'storage', 'up',
    ];

    public static function prefix(): string
    {
        $value = strtolower(trim((string) config('duvento.admin_path', 'admin'), '/'));

        return self::isValid($value) ? $value : 'admin';
    }

    public static function url(string $suffix = ''): string
    {
        return '/'.self::prefix().($suffix === '' ? '' : '/'.ltrim($suffix, '/'));
    }

    public static function generate(): string
    {
        return 'adm-'.strtolower(Str::random(8));
    }

    public static function isValid(string $path): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9_-]{1,31}$/', $path) === 1
            && ! in_array($path, self::RESERVED, true);
    }

    /** @return list<string> */
    public static function reserved(): array
    {
        return self::RESERVED;
    }
}
