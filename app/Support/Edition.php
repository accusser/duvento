<?php

namespace App\Support;

use App\Enums\WorkspacePlan;
use App\Models\Workspace;

final class Edition
{
    public static function current(): string
    {
        return config('edition.edition', 'self-host');
    }

    public static function isCloud(): bool
    {
        return self::current() === 'cloud';
    }

    public static function isSelfHost(): bool
    {
        return ! self::isCloud();
    }

    public static function enabled(string $feature, ?Workspace $workspace = null): bool
    {
        if (! self::isCloud() || ! config("edition.cloud_features.{$feature}", false)) {
            return false;
        }

        if ($feature === 'white_label') {
            $plan = $workspace?->plan ?? auth()->user()?->currentWorkspace?->plan;

            return $plan === WorkspacePlan::Agency;
        }

        return true;
    }
}
