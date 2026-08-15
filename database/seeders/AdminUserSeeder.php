<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        AdminUser::query()->updateOrCreate(
            ['email' => 'admin@duvento.local'],
            ['name' => 'Duvento Admin', 'password' => 'password'],
        );
    }
}
