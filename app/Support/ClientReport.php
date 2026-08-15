<?php

namespace App\Support;

use App\Enums\AssetOwner;
use App\Enums\AssetPayer;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Client;
use Illuminate\Support\Collection;

final class ClientReport
{
    public static function assets(Client $client): Collection
    {
        return $client->assets
            ->sortBy(fn (Asset $asset) => $asset->expires_at?->timestamp ?? PHP_INT_MAX)
            ->values();
    }

    public static function counts(Client $client): array
    {
        $assets = $client->assets;

        return [
            'total' => $assets->count(),
            'expired' => $assets->filter(fn (Asset $asset) => $asset->status === AssetStatus::Expired)->count(),
            'critical' => $assets->filter(fn (Asset $asset) => $asset->status === AssetStatus::Critical)->count(),
            'month' => $assets->filter(fn (Asset $asset) => in_array($asset->status, [
                AssetStatus::Critical,
                AssetStatus::Urgent,
                AssetStatus::Upcoming,
            ], true))->count(),
            'unknown_date' => $assets->filter(fn (Asset $asset) => $asset->status === AssetStatus::Unknown)->count(),
            'unknown_owner' => $assets->filter(fn (Asset $asset) => $asset->owner === AssetOwner::Unknown)->count(),
            'unknown_payer' => $assets->filter(fn (Asset $asset) => $asset->payer === AssetPayer::Unknown)->count(),
        ];
    }

    public static function recommendation(Asset $asset): ?string
    {
        return match (true) {
            $asset->expires_at === null => 'expiry',
            in_array($asset->status, [AssetStatus::Expired, AssetStatus::Critical], true) => 'renew',
            $asset->owner === AssetOwner::Unknown => 'owner',
            $asset->payer === AssetPayer::Unknown => 'payer',
            default => null,
        };
    }

    public static function actions(Client $client): Collection
    {
        return self::assets($client)
            ->flatMap(function (Asset $asset) {
                $items = collect();

                if ($asset->expires_at === null) {
                    $items->push(['key' => 'expiry', 'asset' => $asset]);
                } elseif (in_array($asset->status, [AssetStatus::Expired, AssetStatus::Critical], true)) {
                    $items->push(['key' => 'renew', 'asset' => $asset]);
                }

                if ($asset->owner === AssetOwner::Unknown) {
                    $items->push(['key' => 'owner', 'asset' => $asset]);
                }

                if ($asset->payer === AssetPayer::Unknown) {
                    $items->push(['key' => 'payer', 'asset' => $asset]);
                }

                return $items;
            })
            ->values();
    }
}
