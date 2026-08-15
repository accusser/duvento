<?php

namespace App\Filament\Concerns;

trait HasAdminLexicon
{
    public static function getNavigationLabel(): string
    {
        return __(static::adminLexicon().'.nav');
    }

    public static function getModelLabel(): string
    {
        return __(static::adminLexicon().'.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::adminLexicon().'.plural');
    }

    abstract public static function adminLexicon(): string;
}
