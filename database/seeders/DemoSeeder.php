<?php

namespace Database\Seeders;

use App\Enums\AssetOwner;
use App\Enums\AssetPayer;
use App\Enums\AutoRenew;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Client;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\WorkspaceProvisioner;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $types = AssetType::query()->whereNull('workspace_id')->get()->keyBy('key');
        $provisioner = app(WorkspaceProvisioner::class);

        $north = $provisioner->create(
            'Северная студия',
            User::factory()->create([
                'name' => 'Александр Северный',
                'email' => 'alex@severnaya.example',
            ]),
        );

        $pixel = $provisioner->create(
            'Pixel & Co',
            User::factory()->create([
                'name' => 'Мария Пиксель',
                'email' => 'maria@pixel.example',
            ]),
        );

        $atelier = Client::query()->create([
            'workspace_id' => $north->id,
            'name' => 'Nordic Atelier',
            'email' => 'hello@nordic-atelier.ru',
            'notes' => 'Интернет-магазин керамики. Домен и SSL на нас.',
        ]);

        $bakery = Client::query()->create([
            'workspace_id' => $north->id,
            'name' => 'Пекарня «Тихо»',
            'email' => 'info@tiho-bakery.ru',
        ]);

        $harbor = Client::query()->create([
            'workspace_id' => $pixel->id,
            'name' => 'Harbor Books',
            'email' => 'web@harbor-books.com',
        ]);

        $this->asset($north, $atelier, $types['domain'], [
            'name' => 'nordic-atelier.ru',
            'expires_at' => now()->addDays(12),
            'auto_renew' => AutoRenew::No,
            'owner' => AssetOwner::Agency,
            'payer' => AssetPayer::Agency,
        ]);

        $this->asset($north, $atelier, $types['ssl'], [
            'name' => 'nordic-atelier.ru',
            'expires_at' => now()->addDays(4),
            'auto_renew' => AutoRenew::Yes,
            'owner' => AssetOwner::Agency,
            'payer' => AssetPayer::Agency,
            'ssl_check_enabled' => true,
        ]);

        $this->asset($north, $atelier, $types['hosting'], [
            'name' => 'Timeweb VPS',
            'expires_at' => now()->addDays(86),
            'auto_renew' => AutoRenew::Yes,
            'owner' => AssetOwner::Agency,
            'payer' => AssetPayer::Client,
        ]);

        $this->asset($north, $bakery, $types['plugin_license'], [
            'name' => 'Yoast SEO Premium',
            'expires_at' => now()->addDays(22),
            'auto_renew' => AutoRenew::Unknown,
            'owner' => AssetOwner::Client,
            'payer' => AssetPayer::Client,
        ]);

        $this->asset($pixel, $harbor, $types['domain'], [
            'name' => 'harbor-books.com',
            'expires_at' => now()->addDays(2),
            'auto_renew' => AutoRenew::No,
            'owner' => AssetOwner::Agency,
            'payer' => AssetPayer::Agency,
        ]);

        $this->asset($pixel, $harbor, $types['other'], [
            'name' => 'Договор на поддержку',
            'expires_at' => null,
            'auto_renew' => AutoRenew::Unknown,
            'owner' => AssetOwner::Unknown,
            'payer' => AssetPayer::Unknown,
            'notes' => 'Дата продления не зафиксирована',
        ]);
    }

    private function asset($workspace, Client $client, AssetType $type, array $attributes): Asset
    {
        $asset = Asset::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'asset_type_id' => $type->id,
            ...$attributes,
        ]);

        ActivityLogger::log($workspace, 'asset.created', $asset, ['name' => $asset->name], $workspace->users()->first());

        return $asset;
    }
}
