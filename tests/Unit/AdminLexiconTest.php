<?php

namespace Tests\Unit;

use App\Support\AppLocale;
use Tests\TestCase;

class AdminLexiconTest extends TestCase
{
    public function test_app_lexicons_have_the_same_keys(): void
    {
        foreach (AppLocale::codes() as $locale) {
            $this->assertSameKeys('app', $locale);
        }
    }

    public function test_admin_lexicons_have_the_same_keys(): void
    {
        foreach (AppLocale::codes() as $locale) {
            $this->assertSameKeys('admin', $locale);
        }
    }

    public function test_validation_lexicons_have_the_same_keys(): void
    {
        foreach (AppLocale::codes() as $locale) {
            $this->assertSameKeys('validation', $locale);
        }
    }

    public function test_ru_and_en_auth_lexicons_have_the_same_keys(): void
    {
        $this->assertSameKeys('auth');
        $this->assertSameKeys('passwords');
        $this->assertSameKeys('pagination');
    }

    private function assertSameKeys(string $file, string $locale = 'ru'): void
    {
        $localeKeys = include base_path("lang/{$locale}/{$file}.php");
        $en = include base_path("lang/en/{$file}.php");

        $this->assertSame($this->keys($en), $this->keys($localeKeys), "Lexicon mismatch: lang/{$locale}/{$file}.php");
    }

    private function keys(array $items, string $prefix = ''): array
    {
        $keys = [];

        foreach ($items as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $keys = [...$keys, ...$this->keys($value, $path)];

                continue;
            }

            $keys[] = $path;
        }

        sort($keys);

        return $keys;
    }
}
