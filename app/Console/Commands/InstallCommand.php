<?php

namespace App\Console\Commands;

use App\Install\InstallerState;
use App\Install\InstallService;
use App\Support\AdminPath;
use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'duvento:install
        {--url= : Публичный URL приложения}
        {--email= : Email администратора}
        {--name= : Имя администратора}
        {--workspace= : Название рабочего пространства}
        {--admin-path= : Секретный путь админки}
        {--locale=ru : Язык приложения}';

    protected $description = 'Завершить чистую установку Duvento';

    public function handle(InstallService $installer): int
    {
        if (InstallerState::isLocked()) {
            $this->error('Duvento уже установлен.');

            return self::FAILURE;
        }

        $url = rtrim((string) ($this->option('url') ?: config('app.url')), '/');
        $email = (string) ($this->option('email') ?: $this->ask('Email администратора'));
        $name = (string) ($this->option('name') ?: $this->ask('Имя администратора'));
        $workspace = (string) ($this->option('workspace') ?: $this->ask('Название рабочего пространства', 'Моя компания'));
        $adminPath = strtolower((string) ($this->option('admin-path') ?: AdminPath::generate()));
        $locale = (string) $this->option('locale');
        $password = (string) $this->secret('Пароль (минимум 10 символов, буквы и цифры)');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! AdminPath::isValid($adminPath)
            || strlen($password) < 10
            || ! preg_match('/[a-zA-Z]/', $password)
            || ! preg_match('/\d/', $password)) {
            $this->error('Проверьте email, пароль и адрес админки.');

            return self::FAILURE;
        }

        try {
            $installer->finish([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'workspace' => $workspace,
                'admin_path' => $adminPath,
                'locale' => $locale,
            ], $url, str_starts_with($url, 'https://'));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Duvento установлен.');
        $this->line('Админка: '.$url.AdminPath::url());

        return self::SUCCESS;
    }
}
