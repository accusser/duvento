<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use App\Support\PasswordGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAdmin extends Command
{
    protected $signature = 'duvento:make-admin {email} {name?} {--password=}';

    protected $description = 'Создать или обновить администратора Filament';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = trim((string) ($this->argument('name') ?: Str::before($email, '@')));
        $password = (string) ($this->option('password') ?: PasswordGenerator::generate());

        AdminUser::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password],
        );

        $this->info("Admin: {$email}");
        $this->line("Password: {$password}");

        return self::SUCCESS;
    }
}
