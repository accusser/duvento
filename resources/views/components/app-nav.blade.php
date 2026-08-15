@php
    $user = auth()->user();
    $workspace = $user?->currentWorkspace;
    $isOwner = $user && $workspace && $user->isOwnerOf($workspace);
    $unread = $workspace
        ? $workspace->activityLogs()->inbox()->whereNull('read_at')->count()
        : 0;
    $unreadSupport = $workspace
        ? $workspace->ticketMessages()
            ->where('author_type', \App\Enums\TicketAuthorType::Admin->value)
            ->whereNull('read_at')
            ->count()
        : 0;
    $items = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'mdi-view-dashboard-outline', 'label' => __('app.nav.dashboard')],
        ['route' => 'clients', 'match' => 'clients', 'icon' => 'mdi-account-multiple-outline', 'label' => __('app.nav.clients')],
        ['route' => 'assets', 'match' => 'assets*', 'icon' => 'mdi-cube-outline', 'label' => __('app.nav.assets')],
        ['route' => 'reports', 'match' => 'reports*', 'icon' => 'mdi-file-chart-outline', 'label' => __('app.nav.reports')],
        ['route' => 'notifications', 'match' => 'notifications', 'icon' => 'mdi-bell-outline', 'label' => __('app.nav.notifications'), 'badge' => $unread],
        ['route' => 'activity', 'match' => 'activity', 'icon' => 'mdi-history', 'label' => __('app.nav.activity')],
        ['route' => 'support', 'match' => 'support*', 'icon' => 'mdi-lifebuoy', 'label' => __('app.nav.support'), 'badge' => $unreadSupport],
    ];
    $settings = [
        ['route' => 'settings.account', 'match' => 'settings.account', 'icon' => 'mdi-account-outline', 'label' => __('app.nav.account')],
    ];
    if ($isOwner) {
        $settings[] = ['route' => 'settings.team', 'match' => 'settings.team', 'icon' => 'mdi-account-multiple-plus-outline', 'label' => __('app.nav.team')];
        $settings[] = ['route' => 'settings.reminders', 'match' => 'settings.reminders', 'icon' => 'mdi-bell-outline', 'label' => __('app.nav.reminders')];
        $settings[] = ['route' => 'settings.types', 'match' => 'settings.types', 'icon' => 'mdi-shape-outline', 'label' => __('app.nav.types')];
        $settings[] = ['route' => 'import', 'match' => 'import', 'icon' => 'mdi-upload-outline', 'label' => __('app.nav.import')];
        $settings[] = ['route' => 'export', 'match' => 'export', 'icon' => 'mdi-database-export-outline', 'label' => __('app.nav.export')];
        if (\App\Support\Edition::enabled('public_api') || \App\Support\Edition::enabled('webhooks')) {
            $settings[] = ['route' => 'settings.api', 'match' => 'settings.api', 'icon' => 'mdi-api', 'label' => __('app.nav.api')];
        }
        if (\App\Support\Edition::enabled('billing')) {
            $settings[] = ['route' => 'settings.billing', 'match' => 'settings.billing', 'icon' => 'mdi-credit-card-outline', 'label' => __('app.nav.billing')];
        }
    }
    if (\App\Support\Edition::enabled('white_label', $workspace)) {
        $settings[] = ['route' => 'reports.clients', 'match' => 'reports.clients', 'icon' => 'mdi-file-pdf-box', 'label' => __('app.nav.client_report'), 'navigate' => false];
    }
@endphp

<ul class="menu">
    @foreach ($items as $item)
        <li class="{{ request()->routeIs($item['match']) ? 'active' : '' }}">
            <a href="{{ route($item['route']) }}" wire:navigate>
                <i class="mdi {{ $item['icon'] }}"></i>
                <span class="menu-text">{{ $item['label'] }}</span>
                @if (($item['badge'] ?? 0) > 0)
                    <span class="badge badge-soft-danger ms-auto">{{ $item['badge'] }}</span>
                @endif
            </a>
        </li>
    @endforeach

    @foreach ($settings as $item)
        <li class="{{ request()->routeIs($item['match']) ? 'active' : '' }}">
            <a href="{{ route($item['route']) }}" @if ($item['navigate'] ?? true) wire:navigate @endif>
                <i class="mdi {{ $item['icon'] }}"></i>
                <span class="menu-text">{{ $item['label'] }}</span>
            </a>
        </li>
    @endforeach
</ul>
