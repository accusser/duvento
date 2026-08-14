<?php

namespace App\Support;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Workspace;
use Illuminate\Support\Collection;

final class AssetQuery
{
    public static function filtered(
        Workspace $workspace,
        ?string $search = null,
        ?string $status = null,
        ?int $clientId = null,
    ): Collection {
        $query = $workspace->assets()->with(['client', 'assetType']);

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $assets = $query
            ->orderByRaw('case when expires_at is null then 1 else 0 end')
            ->orderBy('expires_at')
            ->get();

        if (filled($status)) {
            $assets = $assets->filter(function (Asset $asset) use ($status) {
                $key = $asset->status->dashboardKey();

                return $status === 'critical'
                    ? in_array($asset->status, [AssetStatus::Critical, AssetStatus::Expired], true)
                    : $key === $status;
            })->values();
        }

        return $assets;
    }

    public static function counts(Workspace $workspace): array
    {
        $base = array_fill_keys(AssetStatus::dashboardKeys(), 0);

        return $workspace->assets->reduce(function (array $counts, Asset $asset) {
            $counts[$asset->status->dashboardKey()]++;

            return $counts;
        }, $base);
    }
}
