<?php

namespace App\Enums;

enum AssetPayer: string
{
    case Agency = 'agency';
    case Client = 'client';
    case Unknown = 'unknown';
}
