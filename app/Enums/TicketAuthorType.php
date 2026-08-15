<?php

namespace App\Enums;

enum TicketAuthorType: string
{
    case Client = 'client';
    case Admin = 'admin';
}
