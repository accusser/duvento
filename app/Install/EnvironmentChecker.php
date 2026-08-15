<?php

namespace App\Install;

final class EnvironmentChecker
{
    /**
     * @return array{ok: bool, checks: list<array{label: string, ok: bool, detail: string}>}
     */
    public function check(): array
    {
        $checks = [];
        $this->add($checks, 'PHP 8.3+', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION);

        foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'intl'] as $extension) {
            $this->add($checks, "Расширение {$extension}", extension_loaded($extension), extension_loaded($extension) ? 'Установлено' : 'Не найдено');
        }

        $hasDatabaseDriver = extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite');
        $this->add($checks, 'Драйвер базы данных', $hasDatabaseDriver, 'Нужен pdo_mysql или pdo_sqlite');

        foreach ([
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            '.env' => base_path(),
        ] as $label => $path) {
            $writable = is_writable($path)
                && ($label !== '.env' || ! is_file(base_path('.env')) || is_writable(base_path('.env')));
            $this->add($checks, "Запись: {$label}", $writable, $path);
        }

        return [
            'ok' => collect($checks)->every(fn (array $check): bool => $check['ok']),
            'checks' => $checks,
        ];
    }

    /**
     * @param  list<array{label: string, ok: bool, detail: string}>  $checks
     */
    private function add(array &$checks, string $label, bool $ok, string $detail): void
    {
        $checks[] = compact('label', 'ok', 'detail');
    }
}
