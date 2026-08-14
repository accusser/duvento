<?php

namespace App\Contracts;

use App\Enums\WorkspacePlan;
use App\Models\Workspace;

interface BillingGateway
{
    public function checkoutUrl(Workspace $workspace, WorkspacePlan $plan): string;

    public function cancel(Workspace $workspace): void;
}
