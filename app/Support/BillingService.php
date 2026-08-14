<?php

namespace App\Support;

use App\Enums\SubscriptionStatus;
use App\Enums\WorkspacePlan;
use App\Models\PaymentEvent;
use App\Models\Subscription;
use App\Models\Workspace;

final class BillingService
{
    public function activate(Workspace $workspace, WorkspacePlan $plan, string $providerId, int $amount = 0): Subscription
    {
        $workspace->update(['plan' => $plan]);

        $subscription = $workspace->subscriptions()->latest()->first()
            ?? $workspace->subscriptions()->make();

        $subscription->fill([
            'workspace_id' => $workspace->id,
            'plan' => $plan,
            'status' => SubscriptionStatus::Active,
            'billing_provider_id' => $providerId,
            'trial_ends_at' => null,
            'ends_at' => null,
        ])->save();

        PaymentEvent::query()->create([
            'workspace_id' => $workspace->id,
            'type' => 'paid',
            'plan' => $plan,
            'amount' => $amount,
            'provider_id' => $providerId,
        ]);

        return $subscription;
    }

    public function cancel(Workspace $workspace): void
    {
        $workspace->update(['plan' => WorkspacePlan::FreeTrial]);
        $workspace->subscriptions()->latest()->first()?->update([
            'status' => SubscriptionStatus::Canceled,
            'ends_at' => now(),
        ]);

        PaymentEvent::query()->create([
            'workspace_id' => $workspace->id,
            'type' => 'canceled',
            'plan' => $workspace->plan,
        ]);
    }
}
