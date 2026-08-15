<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionStatus;
use App\Enums\TicketStatus;
use App\Filament\Pages\InstanceHealth;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Resources\WaitlistSignups\WaitlistSignupResource;
use App\Filament\Resources\Workspaces\WorkspaceResource;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\WaitlistSignup;
use App\Models\Workspace;
use App\Support\Edition;
use App\Support\HealthBannerDismissal;
use App\Support\InstanceHealth as Health;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class StatsOverview extends Widget
{
    protected string $view = 'filament.widgets.stats-overview';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected static ?int $sort = -2;

    protected function getViewData(): array
    {
        $since = Carbon::now()->subWeek();
        $workspaces = Workspace::query()->count();
        $workspacesPrev = Workspace::query()->where('created_at', '<', $since)->count();
        $clients = Client::query()->count();
        $clientsPrev = Client::query()->where('created_at', '<', $since)->count();
        $assets = Asset::query()->count();
        $assetsPrev = Asset::query()->where('created_at', '<', $since)->count();
        $critical = Asset::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(7))
            ->count();
        $criticalPrev = Asset::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(7))
            ->where('created_at', '<', $since)
            ->count();
        $openTickets = Ticket::query()
            ->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])
            ->count();
        $openTicketsPrev = Ticket::query()
            ->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])
            ->where('created_at', '<', $since)
            ->count();
        $unreadTickets = Ticket::query()->unreadFromClients()->count();
        $newWorkspaces = Workspace::query()->where('created_at', '>=', $since)->count();
        $newWorkspacesPrev = Workspace::query()
            ->where('created_at', '>=', $since->copy()->subWeek())
            ->where('created_at', '<', $since)
            ->count();

        $ticketStat = $this->stat('tickets', $openTickets, $openTicketsPrev, $unreadTickets > 0 ? 'ribbon-rose' : 'ribbon-cool', $unreadTickets > 0 ? 'rose' : 'cool', 'mdi-lifebuoy', TicketResource::getUrl());
        if ($unreadTickets > 0) {
            $ticketStat['caption'] = __('admin.stats.tickets_unread', ['count' => $unreadTickets]);
        }

        $stats = [
            $this->stat('workspaces', $workspaces, $workspacesPrev, 'ribbon-mint', 'mint', 'mdi-office-building-outline', WorkspaceResource::getUrl()),
            $this->stat('clients', $clients, $clientsPrev, 'ribbon-cool', 'cool', 'mdi-account-multiple-outline', ClientResource::getUrl()),
            $this->stat('assets', $assets, $assetsPrev, 'ribbon-amber', 'amber', 'mdi-cube-outline', AssetResource::getUrl()),
            $this->stat(
                'critical',
                $critical,
                $criticalPrev,
                'ribbon-rose',
                'rose',
                'mdi-alert-outline',
                AssetResource::getUrl(parameters: [
                    'tableFilters' => ['status' => ['value' => 'critical']],
                ]),
            ),
            $ticketStat,
            $this->stat('new_workspaces', $newWorkspaces, $newWorkspacesPrev, 'ribbon-mint', 'mint', 'mdi-plus', WorkspaceResource::getUrl()),
        ];

        if (Edition::isCloud()) {
            $active = Subscription::query()->where('status', SubscriptionStatus::Active);
            $subscriptions = (clone $active)->count();
            $waitlist = WaitlistSignup::query()->count();
            $mrr = (clone $active)->get()->sum(fn (Subscription $s) => (int) config('billing.plans.'.$s->plan->value.'.price', 0));
            $subscriptionsPrev = (clone $active)->where('created_at', '<', $since)->count();
            $waitlistPrev = WaitlistSignup::query()->where('created_at', '<', $since)->count();
            $mrrPrev = (clone $active)->where('created_at', '<', $since)->get()
                ->sum(fn (Subscription $s) => (int) config('billing.plans.'.$s->plan->value.'.price', 0));

            $stats[] = $this->stat('subscriptions', $subscriptions, $subscriptionsPrev, 'ribbon-cool', 'cool', 'mdi-credit-card-outline', SubscriptionResource::getUrl());
            $stats[] = $this->stat('mrr', $mrr, $mrrPrev, 'ribbon-amber', 'amber', 'mdi-cash', SubscriptionResource::getUrl(), '$');
            $stats[] = $this->stat('waitlist', $waitlist, $waitlistPrev, 'ribbon-rose', 'rose', 'mdi-email-outline', WaitlistSignupResource::getUrl());
        }

        return [
            'alerts' => HealthBannerDismissal::visible(auth('admin')->id(), $this->alerts()),
            'healthUrl' => InstanceHealth::getUrl(),
            'stats' => $stats,
        ];
    }

    public function dismissHealthAlerts(): void
    {
        HealthBannerDismissal::dismiss(auth('admin')->id(), $this->alerts());
    }

    /** @return list<array{key: string, label: string, ok: bool, level: string, detail: string}> */
    private function alerts(): array
    {
        return collect(app(Health::class)->checks())->where('ok', false)->values()->all();
    }

    private function stat(
        string $key,
        int $current,
        int $previous,
        string $ribbon,
        string $iconWrap,
        string $mdi,
        string $url,
        string $prefix = '',
    ): array {
        $trend = $this->trend($current, $previous);

        return [
            'label' => __("admin.stats.{$key}"),
            'value' => $prefix.number_format($current),
            'caption' => __($trend['up'] ? 'admin.stats.caption_up' : 'admin.stats.caption_down'),
            'percent' => $trend['percent'],
            'up' => $trend['up'],
            'ribbon' => $ribbon,
            'iconWrap' => $iconWrap,
            'mdi' => $mdi,
            'url' => $url,
        ];
    }

    private function trend(int $current, int $previous): array
    {
        if ($previous === 0) {
            return [
                'percent' => number_format($current > 0 ? 100 : 0, 2, '.', ''),
                'up' => $current >= $previous,
            ];
        }

        $raw = (($current - $previous) / $previous) * 100;

        return [
            'percent' => number_format(abs($raw), 2, '.', ''),
            'up' => $raw >= 0,
        ];
    }
}
