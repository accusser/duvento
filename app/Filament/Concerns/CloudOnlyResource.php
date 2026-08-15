<?php

namespace App\Filament\Concerns;

use App\Support\Edition;

trait CloudOnlyResource
{
    public static function canViewAny(): bool
    {
        return Edition::isCloud();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Edition::isCloud();
    }
}
