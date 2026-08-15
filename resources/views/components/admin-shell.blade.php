@php
    $user = filament()->auth()->user();
    $userName = $user ? filament()->getUserName($user) : '';
    $homeUrl = filament()->getHomeUrl() ?? url(\App\Support\AdminPath::url());
    $profileUrl = filament()->getProfileUrl();
    $logoutUrl = filament()->getLogoutUrl();
    $currentLocale = app()->getLocale();
    $navItems = collect(filament()->getNavigation())
        ->flatMap(fn ($group) => $group->getItems())
        ->values();
    $navBadges = $navItems->mapWithKeys(
        fn ($item) => [(string) $item->getUrl() => $item->getBadge()]
    );
    $alerts = \App\Models\ActivityLog::query()
        ->inbox()
        ->with('workspace')
        ->latest()
        ->limit(5)
        ->get();
    $unreadAlerts = \App\Models\ActivityLog::query()->inbox()->whereNull('read_at')->count();
    $ticketAlerts = \App\Models\Ticket::query()
        ->unreadFromClients()
        ->with(['workspace', 'user'])
        ->latest('last_message_at')
        ->limit(5)
        ->get();
    $unreadTickets = \App\Models\TicketMessage::query()
        ->where('author_type', \App\Enums\TicketAuthorType::Client->value)
        ->whereNull('read_at')
        ->whereNull('dismissed_at')
        ->count();
    $unreadTotal = $unreadAlerts + $unreadTickets;
    $mdiFor = function (string $url): string {
        return match (true) {
            str_contains($url, 'workspaces') => 'mdi-office-building-outline',
            str_contains($url, 'admin-users') => 'mdi-shield-account-outline',
            str_contains($url, 'users') => 'mdi-account-group-outline',
            str_contains($url, 'clients') => 'mdi-account-multiple-outline',
            str_contains($url, 'asset-types') => 'mdi-shape-outline',
            str_contains($url, 'assets') => 'mdi-cube-outline',
            str_contains($url, 'activity') => 'mdi-history',
            str_contains($url, 'tickets') => 'mdi-lifebuoy',
            str_contains($url, 'export-data') => 'mdi-database-export-outline',
            str_contains($url, 'instance-health') => 'mdi-heart-pulse',
            str_contains($url, 'subscriptions') => 'mdi-credit-card-outline',
            str_contains($url, 'payment') => 'mdi-bank-outline',
            str_contains($url, 'waitlist') => 'mdi-email-outline',
            default => 'mdi-view-dashboard-outline',
        };
    };
@endphp

<div class="app">
    <header class="topbar">
        <div class="topbar-brandzone">
            <a href="{{ $homeUrl }}" class="brand" wire:navigate aria-label="Duvento">
                <span class="brand-mark">D</span>
                <span class="brand-text">Duvento</span>
            </a>
        </div>
        <div class="topbar-inner">
            <div class="topbar-left">
                <button class="icon-btn" type="button" data-toggle="sidebar" data-tip aria-label="{{ __('admin.header.menu') }}">
                    <i class="mdi mdi-menu"></i>
                    <span class="ny-tip">{{ __('admin.header.menu') }}</span>
                </button>
                @livewire('admin.global-search', key('admin-global-search'))
            </div>
            <div class="topbar-right">
                <div class="dropdown d-none d-lg-block">
                    <button class="icon-btn" type="button" data-bs-toggle="dropdown" data-tip aria-label="{{ __('admin.header.sections') }}">
                        <i class="mdi mdi-apps"></i>
                        <span class="ny-tip">{{ __('admin.header.sections') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-wide">
                        <div class="d-flex align-items-center justify-content-between px-2 pb-2 mb-1 border-bottom">
                            <h6 class="m-0 fw-bold ny-fs-85">{{ __('admin.header.sections') }}</h6>
                        </div>
                        <div class="apps-grid">
                            @foreach ($navItems as $item)
                                <a href="{{ $item->getUrl() }}" wire:navigate>
                                    <i class="mdi {{ $mdiFor((string) $item->getUrl()) }}"></i>{{ $item->getLabel() }}
                                    @if ($badge = $navBadges[(string) $item->getUrl()])
                                        <span class="badge badge-soft-danger">{{ $badge }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="icon-btn" type="button" data-bs-toggle="dropdown" data-tip aria-label="{{ __('admin.header.language') }}">
                        <i class="mdi mdi-translate"></i>
                        <span class="ny-tip">{{ __('admin.header.language') }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ __('admin.header.language') }}</h6></li>
                        @foreach (\App\Support\AppLocale::all() as $code => $locale)
                            <li>
                                <a class="dropdown-item {{ $currentLocale === $code ? 'active' : '' }}" href="{{ url(\App\Support\AdminPath::url("locale/{$code}")) }}">
                                    <span class="me-2">{{ $locale['flag'] }}</span>{{ $locale['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <button class="icon-btn" type="button" data-toggle="theme" data-tip aria-label="{{ __('admin.header.theme') }}">
                    <i class="mdi mdi-weather-night"></i>
                    <span class="ny-tip">{{ __('admin.header.theme') }}</span>
                </button>

                <div class="dropdown">
                    <button class="icon-btn" type="button" data-bs-toggle="dropdown" data-tip aria-label="{{ __('admin.header.notifications') }}">
                        <i class="mdi mdi-bell-outline"></i>
                        @if ($unreadTotal > 0)
                            <span class="badge-num">{{ $unreadTotal }}</span>
                        @endif
                        <span class="ny-tip">{{ __('admin.header.notifications') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-wide">
                        <div class="d-flex align-items-center justify-content-between px-2 pb-2 mb-1 border-bottom">
                            <h6 class="m-0 fw-bold">{{ __('admin.header.notifications') }}</h6>
                            <a href="{{ \App\Filament\Pages\AdminNotifications::getUrl() }}" class="small">{{ __('admin.header.notifications_all') }}</a>
                        </div>
                        <div class="notif-list">
                            @foreach ($ticketAlerts as $ticket)
                                <a href="{{ \App\Filament\Resources\Tickets\TicketResource::getUrl('view', ['record' => $ticket]) }}" class="notif-item dropdown-item" wire:navigate>
                                    <span class="notif-ic bg-primary-soft"><i class="mdi mdi-lifebuoy"></i></span>
                                    <div class="notif-meta">
                                        <div><strong>{{ $ticket->subject }}</strong></div>
                                        <small>{{ $ticket->workspace?->name }} · {{ $ticket->user?->name }} · {{ $ticket->last_message_at?->format('Y-m-d H:i') }}</small>
                                    </div>
                                </a>
                            @endforeach
                            @forelse ($alerts as $alert)
                                <a href="{{ \App\Filament\Resources\ActivityLogs\ActivityLogResource::getUrl('view', ['record' => $alert]) }}" class="notif-item dropdown-item" wire:navigate>
                                    <span class="notif-ic bg-primary-soft"><i class="mdi mdi-bell-outline"></i></span>
                                    <div class="notif-meta">
                                        <div><strong>{{ $alert->properties['name'] ?? $alert->action }}</strong></div>
                                        <small>{{ $alert->workspace?->name }} · {{ $alert->created_at->format('Y-m-d H:i') }}</small>
                                    </div>
                                </a>
                            @empty
                                @if ($ticketAlerts->isEmpty())
                                    <p class="px-3 py-3 text-muted small mb-0">{{ __('admin.header.notifications_empty') }}</p>
                                @endif
                            @endforelse
                        </div>
                    </div>
                </div>

                <button class="icon-btn d-none d-sm-inline-flex" type="button" data-toggle="fullscreen" data-tip aria-label="{{ __('admin.header.fullscreen') }}">
                    <i class="mdi mdi-fullscreen"></i>
                    <span class="ny-tip">{{ __('admin.header.fullscreen') }}</span>
                </button>

                <div class="dropdown">
                    <button class="icon-btn user-menu-btn" type="button" data-bs-toggle="dropdown">
                        <span class="avatar avatar-init">{{ mb_substr($userName, 0, 1) }}</span>
                        <span class="user-info">
                            <strong>{{ $userName }}</strong>
                            <small>{{ __('admin.header.role_admin') }}</small>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ $userName }}</h6></li>
                        @if ($profileUrl)
                            <li><a class="dropdown-item" href="{{ $profileUrl }}" wire:navigate><i class="mdi mdi-account-outline me-2"></i>{{ __('admin.header.my_profile') }}</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ $logoutUrl }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="mdi mdi-logout me-2"></i>{{ __('admin.header.logout') }}</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <nav class="h-nav">
        @foreach ($navItems as $item)
            <a href="{{ $item->getUrl() }}" class="h-nav-item {{ $item->isActive() ? 'active' : '' }}" wire:navigate>
                <i class="mdi {{ $mdiFor((string) $item->getUrl()) }}"></i>{{ $item->getLabel() }}
                @if ($badge = $navBadges[(string) $item->getUrl()])
                    <span class="badge badge-soft-danger ms-1">{{ $badge }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="{{ $homeUrl }}" class="brand" wire:navigate>
                <span class="brand-mark">D</span>
                <span class="brand-text">Duvento</span>
            </a>
        </div>
        <div class="sidebar-inner">
            <ul class="menu">
                @foreach ($navItems as $item)
                    <li class="{{ $item->isActive() ? 'active' : '' }}">
                        <a href="{{ $item->getUrl() }}" wire:navigate>
                            <i class="mdi {{ $mdiFor((string) $item->getUrl()) }}"></i>
                            <span class="menu-text">{{ $item->getLabel() }}</span>
                            @if ($badge = $navBadges[(string) $item->getUrl()])
                                <span class="badge badge-soft-danger ms-auto">{{ $badge }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </aside>
    <div class="backdrop"></div>
    <div class="content">
        <main class="page">
            {{ $slot }}
        </main>
        <footer class="app-footer">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>© {{ date('Y') }} <strong class="text-gradient">Duvento</strong></div>
            </div>
        </footer>
    </div>
</div>
