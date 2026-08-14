<?php

namespace App\Enums;

enum AutoRenew: string
{
    case Yes = 'yes';
    case No = 'no';
    case Unknown = 'unknown';
}
