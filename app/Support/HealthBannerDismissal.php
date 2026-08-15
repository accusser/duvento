<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Hides the dashboard health banner for a while. The dismissal is bound to the
 * problems that were visible, so a newly broken check shows the banner again.
 */
final class HealthBannerDismissal
{
    public const HOURS = 24;

    /** @param  list<array{key: string}>  $alerts */
    public static function visible(?int $adminId, array $alerts): array
    {
        if ($adminId === null || $alerts === []) {
            return $alerts;
        }

        return Cache::get(self::key($adminId)) === self::fingerprint($alerts) ? [] : $alerts;
    }

    /** @param  list<array{key: string}>  $alerts */
    public static function dismiss(?int $adminId, array $alerts): void
    {
        if ($adminId === null || $alerts === []) {
            return;
        }

        Cache::put(self::key($adminId), self::fingerprint($alerts), now()->addHours(self::HOURS));
    }

    /** @param  list<array{key: string}>  $alerts */
    private static function fingerprint(array $alerts): string
    {
        return md5(collect($alerts)->pluck('key')->sort()->implode('|'));
    }

    private static function key(int $adminId): string
    {
        return 'admin.health-banner.dismissed.'.$adminId;
    }
}
