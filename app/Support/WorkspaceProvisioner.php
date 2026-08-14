<?php

namespace App\Support;

use App\Enums\ReminderChannel;
use App\Enums\SubscriptionStatus;
use App\Enums\WorkspacePlan;
use App\Models\ReminderRule;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;

final class WorkspaceProvisioner
{
    public function create(string $name, User $owner): Workspace
    {
        SystemCatalog::ensureAssetTypes();

        $plan = Edition::isCloud() ? WorkspacePlan::FreeTrial : WorkspacePlan::SelfHosted;

        $workspace = Workspace::query()->create([
            'name' => $name,
            'plan' => $plan,
        ]);

        $workspace->attachOwner($owner);

        collect([30, 14, 7, 1])->each(fn (int $days) => ReminderRule::query()->create([
            'workspace_id' => $workspace->id,
            'asset_id' => null,
            'days_before' => $days,
            'channel' => ReminderChannel::Email,
        ]));

        if (Edition::isCloud()) {
            Subscription::query()->create([
                'workspace_id' => $workspace->id,
                'plan' => WorkspacePlan::FreeTrial,
                'status' => SubscriptionStatus::Trialing,
                'trial_ends_at' => now()->addDays((int) config('billing.trial_days', 14)),
            ]);
        }

        return $workspace;
    }
}
