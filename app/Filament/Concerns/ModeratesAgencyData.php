<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ModeratesAgencyData
{
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
