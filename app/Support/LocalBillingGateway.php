<?php

namespace App\Support;

use App\Contracts\BillingGateway;
use App\Enums\WorkspacePlan;
use App\Models\Workspace;
use RuntimeException;

final class LocalBillingGateway implements BillingGateway
{
    public function checkoutUrl(Workspace $workspace, WorkspacePlan $plan): string
    {
        throw new RuntimeException('Биллинг доступен только в cloud-сборке.');
    }

    public function cancel(Workspace $workspace): void
    {
        app(BillingService::class)->cancel($workspace);
    }
}
