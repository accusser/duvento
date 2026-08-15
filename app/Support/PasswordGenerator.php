<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PasswordGenerator
{
    public const LENGTH = 16;

    public static function generate(): string
    {
        return Str::password(self::LENGTH);
    }
}
