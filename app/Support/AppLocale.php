<?php

namespace App\Support;

final class AppLocale
{
    /**
     * @return array<string, array{name: string, flag: string}>
     */
    public static function all(): array
    {
        return [
            'ru' => ['name' => 'Русский', 'flag' => '🇷🇺'],
            'uk' => ['name' => 'Українська', 'flag' => '🇺🇦'],
            'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪'],
            'es' => ['name' => 'Español', 'flag' => '🇪🇸'],
            'pl' => ['name' => 'Polski', 'flag' => '🇵🇱'],
            'en' => ['name' => 'English', 'flag' => '🇬🇧'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && isset(self::all()[$locale]);
    }
}
