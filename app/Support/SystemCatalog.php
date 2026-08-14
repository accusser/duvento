<?php

namespace App\Support;

use App\Models\AssetType;

final class SystemCatalog
{
    public static function ensureAssetTypes(): void
    {
        $defaults = [30, 14, 7, 1];

        collect([
            ['key' => 'domain', 'label' => 'Домен', 'icon' => 'globe'],
            ['key' => 'ssl', 'label' => 'SSL-сертификат', 'icon' => 'lock'],
            ['key' => 'hosting', 'label' => 'Хостинг', 'icon' => 'server'],
            ['key' => 'plugin_license', 'label' => 'Лицензия плагина', 'icon' => 'puzzle'],
            ['key' => 'other', 'label' => 'Другое', 'icon' => 'dot'],
        ])->each(fn (array $type) => AssetType::query()->updateOrCreate(
            ['workspace_id' => null, 'key' => $type['key']],
            [...$type, 'default_reminder_days' => $defaults],
        ));
    }
}
