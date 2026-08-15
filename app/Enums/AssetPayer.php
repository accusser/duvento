<?php

namespace App\Enums;

enum AssetPayer: string
{
    case Agency = 'agency';
    case Client = 'client';
    case Unknown = 'unknown';

    public function label(): string
    {
        return __('app.enums.party.'.$this->value);
    }
}
