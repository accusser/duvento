<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class ScheduleHeartbeat
{
    public static function touch(string $job): void
    {
        Cache::put(self::key($job), now()->toIso8601String(), now()->addDays(14));
    }

    public static function last(string $job): ?Carbon
    {
        $value = Cache::get(self::key($job));

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }

    private static function key(string $job): string
    {
        return 'duvento:heartbeat:'.$job;
    }
}
