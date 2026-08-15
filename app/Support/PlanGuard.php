<?php

namespace App\Support;

use App\Models\Workspace;
use Illuminate\Validation\ValidationException;

final class PlanGuard
{
    public function assertCanCreateClient(Workspace $workspace): void
    {
        $this->assertLimit($workspace, 'clients', $workspace->clients()->count());
    }

    public function assertCanCreateAsset(Workspace $workspace): void
    {
        $this->assertLimit($workspace, 'assets', $workspace->assets()->count());
    }

    private function assertLimit(Workspace $workspace, string $key, int $current): void
    {
        if (Edition::isSelfHost()) {
            return;
        }

        if ($workspace->trialExpired()) {
            throw ValidationException::withMessages([
                'name' => __('app.errors.trial_ended'),
            ]);
        }

        $limit = config('billing.plans.'.$workspace->plan->value.'.'.$key);

        if ($limit !== null && $current >= (int) $limit) {
            throw ValidationException::withMessages([
                'name' => __('app.errors.plan_limit', [
                    'limit' => $limit,
                    'resource' => __('app.nav.'.$key),
                ]),
            ]);
        }
    }
}
