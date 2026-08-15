<?php

namespace App\Install;

class EnvWriter
{
    public function path(): string
    {
        return base_path('.env');
    }

    public function ensureExists(): void
    {
        if (! is_file($this->path()) && is_file(base_path('.env.example'))) {
            if (! @copy(base_path('.env.example'), $this->path())) {
                throw new \RuntimeException('Не удалось создать .env. Проверьте права.');
            }
        }

        if (! is_file($this->path())) {
            $content = "APP_NAME=Duvento\nAPP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false\n";
            if (@file_put_contents($this->path(), $content, LOCK_EX) === false) {
                throw new \RuntimeException('Не удалось создать .env.');
            }
        }
    }

    public function generateAppKeyIfEmpty(): string
    {
        $this->ensureExists();
        $content = (string) file_get_contents($this->path());

        if (preg_match('/^APP_KEY=(.+)$/m', $content, $match)
            && trim($match[1], " \t\"'") !== '') {
            return trim($match[1], " \t\"'");
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        $this->setMany(['APP_KEY' => $key], backup: false);

        return $key;
    }

    /**
     * @param  array<string, scalar|null>  $values
     */
    public function setMany(array $values, bool $backup = true): void
    {
        $this->ensureExists();

        if (! is_writable($this->path()) || ! is_writable(dirname($this->path()))) {
            throw new \RuntimeException('Файл .env или каталог проекта недоступен для записи.');
        }

        $content = (string) file_get_contents($this->path());
        foreach ($values as $key => $value) {
            $line = $key.'='.$this->formatValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $content = preg_match($pattern, $content)
                ? (string) preg_replace($pattern, $line, $content, 1)
                : rtrim($content)."\n{$line}\n";
        }

        if ($backup && is_file($this->path())) {
            @copy($this->path(), $this->path().'.backup');
        }

        $temporary = $this->path().'.tmp.'.bin2hex(random_bytes(6));
        if (@file_put_contents($temporary, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Не удалось подготовить .env.');
        }
        @chmod($temporary, 0600);

        if (! @rename($temporary, $this->path())) {
            @unlink($temporary);
            throw new \RuntimeException('Не удалось атомарно обновить .env.');
        }

        $this->clearBootstrapCache();
    }

    public function clearBootstrapCache(): void
    {
        foreach ([
            base_path('bootstrap/cache/config.php'),
            base_path('bootstrap/cache/routes-v7.php'),
            base_path('bootstrap/cache/routes.php'),
        ] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;
        if ($value === '') {
            return '""';
        }

        return preg_match('/[\s#\'"\\\\$`]/', $value)
            ? '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"'
            : $value;
    }
}
