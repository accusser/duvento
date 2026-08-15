<?php

namespace App\Enums;

enum AssetOwner: string
{
    case Agency = 'agency';
    case Client = 'client';
    case Unknown = 'unknown';

    public function label(): string
    {
        return __('app.enums.party.'.$this->value);
    }
}
