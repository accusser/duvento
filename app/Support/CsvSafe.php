<?php

namespace App\Support;

final class CsvSafe
{
    public static function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
            return "'".$value;
        }

        return $value;
    }
}
