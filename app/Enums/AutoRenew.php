<?php

namespace App\Enums;

enum AutoRenew: string
{
    case Yes = 'yes';
    case No = 'no';
    case Unknown = 'unknown';

    public function label(): string
    {
        return __('app.enums.auto_renew.'.$this->value);
    }
}
