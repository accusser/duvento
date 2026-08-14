<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\WaitlistSignup;
use App\Models\Workspace;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $active = Subscription::query()->where('status', SubscriptionStatus::Active)->get();
        $mrr = $active->sum(fn (Subscription $s) => (int) config('billing.plans.'.$s->plan->value.'.price', 0));

        return [
            Stat::make('Воркспейсы', Workspace::query()->count()),
            Stat::make('Активные подписки', $active->count()),
            Stat::make('MRR, $', $mrr),
            Stat::make('Вейтлист', class_exists(WaitlistSignup::class) ? WaitlistSignup::query()->count() : 0),
        ];
    }
}
