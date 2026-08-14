<?php

namespace App\Enums;

enum WorkspacePlan: string
{
    case SelfHosted = 'self-hosted';
    case FreeTrial = 'free-trial';
    case Starter = 'starter';
    case Agency = 'agency';
}
