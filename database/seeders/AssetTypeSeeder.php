<?php

namespace Database\Seeders;

use App\Support\SystemCatalog;
use Illuminate\Database\Seeder;

class AssetTypeSeeder extends Seeder
{
    public function run(): void
    {
        SystemCatalog::ensureAssetTypes();
    }
}
