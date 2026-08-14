<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Support\Edition;
use Illuminate\Console\Command;

class ExpireTrials extends Command
{
    protected $signature = 'duvento:expire-trials';

    protected $description = 'Пометить истёкшие cloud-триалы как past_due';

    public function handle(): int
    {
        if (Edition::isSelfHost()) {
            $this->info('Self-host: пропуск');

            return self::SUCCESS;
        }

        $count = Subscription::query()
            ->where('status', SubscriptionStatus::Trialing)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->update(['status' => SubscriptionStatus::PastDue]);

        $this->info("Expired trials: {$count}");

        return self::SUCCESS;
    }
}
