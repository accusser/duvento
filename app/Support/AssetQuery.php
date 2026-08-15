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
        array $extra = [],
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

        if (! empty($extra['typeId'])) {
            $query->where('asset_type_id', (int) $extra['typeId']);
        }

        if (filled($extra['owner'] ?? null)) {
            $query->where('owner', $extra['owner']);
        }

        if (filled($extra['payer'] ?? null)) {
            $query->where('payer', $extra['payer']);
        }

        if (($extra['expiry'] ?? '') === 'missing') {
            $query->whereNull('expires_at');
        }

        if (($extra['expiry'] ?? '') === 'dated') {
            $query->whereNotNull('expires_at');
        }

        if (! empty($extra['cashflowDays'])) {
            UpcomingPayments::constrain($query, (int) $extra['cashflowDays']);
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

    public static function extras(Workspace $workspace): array
    {
        $assets = $workspace->assets;

        return [
            'clients' => $workspace->clients()->count(),
            'assets' => $assets->count(),
            'expired' => $assets->filter(fn (Asset $asset) => $asset->status === AssetStatus::Expired)->count(),
            'missing_expiry' => $assets->whereNull('expires_at')->count(),
            'unknown_owner' => $assets->filter(fn (Asset $asset) => $asset->owner->value === 'unknown')->count(),
        ];
    }
}
