<?php

namespace App\Enums;

enum ReminderChannel: string
{
    case Email = 'email';
    case Telegram = 'telegram';
    case Slack = 'slack';
}
