<?php

namespace App\Support;

use App\Models\InstanceSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

final class InstanceSettings
{
    public const MAIL = 'mail';

    /** @return array<string, mixed> */
    public function get(string $key, array $default = []): array
    {
        if (! $this->ready()) {
            return $default;
        }

        $value = InstanceSetting::query()->find($key)?->value;

        return is_array($value) ? $value : $default;
    }

    /** @param  array<string, mixed>  $value */
    public function put(string $key, array $value): void
    {
        $this->ensureTable();

        InstanceSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /** @return array{mailer: string, host: string, port: string, username: string, scheme: string, from_address: string, from_name: string, has_password: bool} */
    public function mail(): array
    {
        $saved = $this->get(self::MAIL);

        return [
            'mailer' => (string) ($saved['mailer'] ?? config('mail.default', 'log')),
            'host' => (string) ($saved['host'] ?? config('mail.mailers.smtp.host', '')),
            'port' => (string) ($saved['port'] ?? config('mail.mailers.smtp.port', '587')),
            'username' => (string) ($saved['username'] ?? config('mail.mailers.smtp.username', '')),
            'scheme' => (string) ($saved['scheme'] ?? config('mail.mailers.smtp.scheme', 'tls') ?? ''),
            'from_address' => (string) ($saved['from_address'] ?? config('mail.from.address', '')),
            'from_name' => (string) ($saved['from_name'] ?? config('mail.from.name', 'Duvento')),
            'has_password' => filled($saved['password'] ?? null),
        ];
    }

    /** @param  array<string, mixed>  $input */
    public function saveMail(array $input): void
    {
        $current = $this->get(self::MAIL);
        $password = $input['password'] ?? '';

        $this->put(self::MAIL, [
            'mailer' => $input['mailer'],
            'host' => $input['host'] ?? '',
            'port' => $input['port'] ?? '587',
            'username' => $input['username'] ?? '',
            'password' => filled($password) ? $password : ($current['password'] ?? ''),
            'scheme' => $input['scheme'] ?? '',
            'from_address' => $input['from_address'] ?? '',
            'from_name' => $input['from_name'] ?? '',
        ]);

        $this->apply();
    }

    public function apply(): void
    {
        if (! $this->ready()) {
            return;
        }

        $mail = $this->get(self::MAIL);

        if ($mail === []) {
            return;
        }

        $mailer = (string) ($mail['mailer'] ?? 'log');

        config([
            'mail.default' => $mailer,
            'mail.from.address' => $mail['from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $mail['from_name'] ?? config('mail.from.name'),
        ]);

        if ($mailer !== 'smtp') {
            return;
        }

        config([
            'mail.mailers.smtp.host' => $mail['host'] ?? '',
            'mail.mailers.smtp.port' => (int) ($mail['port'] ?? 587),
            'mail.mailers.smtp.username' => $mail['username'] ?? null,
            'mail.mailers.smtp.password' => $mail['password'] ?? null,
            'mail.mailers.smtp.scheme' => filled($mail['scheme'] ?? null) ? $mail['scheme'] : null,
        ]);
    }

    public function cronCommand(): string
    {
        return '* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1';
    }

    public function ready(): bool
    {
        return Schema::hasTable('instance_settings');
    }

    private function ensureTable(): void
    {
        if ($this->ready()) {
            return;
        }

        Artisan::call('migrate', [
            '--force' => true,
            '--no-interaction' => true,
            '--path' => 'database/migrations/2026_08_15_180000_create_instance_settings_table.php',
        ]);
    }
}
