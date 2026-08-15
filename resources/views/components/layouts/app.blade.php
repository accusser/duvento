@php
    $bsTheme = request()->cookie('nyvora-theme') === 'dark' ? 'dark' : 'light';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ $bsTheme }}" data-ny-scope="app">
<head>
    <x-layouts.theme-head :title="$title ?? config('app.name')" />
</head>
<body class="layout-boxed">
@php
    $user = auth()->user();
    $workspace = $user?->currentWorkspace;
    $isOwner = $user?->ownsCurrentWorkspace() ?? false;
    $alerts = $workspace
        ? $workspace->activityLogs()
            ->inbox()
            ->whereNull('read_at')
            ->latest()
            ->limit(5)
            ->get()
        : collect();
    $unreadAlerts = $alerts->count() > 0
        ? $workspace->activityLogs()->inbox()->whereNull('read_at')->count()
        : 0;
    $supportAlerts = $workspace
        ? $workspace->tickets()
            ->unreadForClient()
            ->latest('last_message_at')
            ->limit(5)
            ->get()
        : collect();
    $unreadSupport = $workspace
        ? $workspace->ticketMessages()
            ->where('author_type', \App\Enums\TicketAuthorType::Admin->value)
            ->whereNull('read_at')
            ->whereNull('dismissed_at')
            ->count()
        : 0;
    $unreadTotal = $unreadAlerts + $unreadSupport;
@endphp
<div class="app">
    <header class="topbar">
        <div class="topbar-brandzone">
            <a href="{{ route('dashboard') }}" class="brand" wire:navigate aria-label="{{ __('app.brand') }}">
                <span class="brand-mark">D</span>
                <span class="brand-text">Duvento</span>
            </a>
        </div>
        <div class="topbar-inner">
            <div class="topbar-left">
                <button class="icon-btn" type="button" data-toggle="sidebar" data-tip aria-label="{{ __('app.header.menu') }}">
                    <i class="mdi mdi-menu"></i>
                    <span class="ny-tip">{{ __('app.header.menu') }}</span>
                </button>
                @livewire('search.global-search', key('workspace-global-search'))
            </div>
            <div class="topbar-right">
                <div class="dropdown d-none d-lg-block">
                    <button class="icon-btn" type="button" data-bs-toggle="dropdown" data-tip aria-label="{{ __('app.nav.sections') }}">
                        <i class="mdi mdi-apps"></i>
                        <span class="ny-tip">{{ __('app.nav.sections') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-wide">
                        <div class="d-flex align-items-center justify-content-between px-2 pb-2 mb-1 border-bottom">
                            <h6 class="m-0 fw-bold ny-fs-85">{{ __('app.nav.sections') }}</h6>
                        </div>
                        <div class="apps-grid">
                            <a href="{{ route('dashboard') }}" wire:navigate><i class="mdi mdi-view-dashboard text-primary"></i>{{ __('app.nav.dashboard') }}</a>
                            <a href="{{ route('clients') }}" wire:navigate><i class="mdi mdi-account-multiple text-success"></i>{{ __('app.nav.clients') }}</a>
                            <a href="{{ route('assets') }}" wire:navigate><i class="mdi mdi-cube text-warning"></i>{{ __('app.nav.assets') }}</a>
                            <a href="{{ route('reports') }}" wire:navigate><i class="mdi mdi-file-chart text-info"></i>{{ __('app.nav.reports') }}</a>
                            <a href="{{ route('notifications') }}" wire:navigate><i class="mdi mdi-bell ny-c-violet"></i>{{ __('app.nav.notifications') }}</a>
                            <a href="{{ route('support') }}" wire:navigate><i class="mdi mdi-lifebuoy text-primary"></i>{{ __('app.nav.support') }}</a>
                            <a href="{{ route('settings.account') }}" wire:navigate><i class="mdi mdi-cog ny-c-orange"></i>{{ __('app.nav.settings') }}</a>
                        </div>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="icon-btn" type="button" data-bs-toggle="dropdown" data-tip aria-label="{{ __('app.header.language') }}">
                        <i class="mdi mdi-translate"></i>
                        <span class="ny-tip">{{ __('app.header.language') }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ __('app.header.language') }}</h6></li>
                        @foreach (\App\Support\AppLocale::all() as $code => $item)
                            <li>
                                <a class="dropdown-item {{ $code === app()->getLocale() ? 'active' : '' }}" href="{{ route('locale', $code) }}">
                                    <span class="me-2">{{ $item['flag'] }}</span>{{ $item['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <button class="icon-btn" type="button" data-toggle="theme" data-tip aria-label="{{ __('app.header.theme') }}">
                    <i class="mdi mdi-weather-night"></i>
                    <span class="ny-tip">{{ __('app.header.theme') }}</span>
                </button>

                <div class="dropdown">
                    <button class="icon-btn" type="button" data-bs-toggle="dropdown" data-tip aria-label="{{ __('app.header.notifications') }}">
                        <i class="mdi mdi-bell-outline"></i>
                        @if ($unreadTotal > 0)
                            <span class="badge-num">{{ $unreadTotal }}</span>
                        @endif
                        <span class="ny-tip">{{ __('app.header.notifications') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-wide">
                        <div class="d-flex align-items-center justify-content-between px-2 pb-2 mb-1 border-bottom">
                            <h6 class="m-0 fw-bold">{{ __('app.header.notifications') }}</h6>
                            <a href="{{ route('notifications') }}" class="small" wire:navigate>{{ __('app.header.notifications_all') }}</a>
                        </div>
                        <div class="notif-list">
                            @foreach ($supportAlerts as $ticket)
                                <a href="{{ route('support.show', $ticket) }}" class="notif-item dropdown-item" wire:navigate>
                                    <span class="notif-ic bg-primary-soft"><i class="mdi mdi-lifebuoy"></i></span>
                                    <div class="notif-meta">
                                        <div><strong>{{ $ticket->subject }}</strong></div>
                                        <small>{{ __('app.support.admin_replied') }} · {{ $ticket->last_message_at?->format('Y-m-d H:i') }}</small>
                                    </div>
                                </a>
                            @endforeach
                            @forelse ($alerts as $alert)
                                <a href="{{ route('notifications') }}" class="notif-item dropdown-item" wire:navigate>
                                    <span class="notif-ic bg-primary-soft"><i class="mdi mdi-bell-outline"></i></span>
                                    <div class="notif-meta">
                                        <div><strong>{{ $alert->properties['name'] ?? $alert->action }}</strong></div>
                                        <small>{{ $alert->created_at->format('Y-m-d H:i') }}</small>
                                    </div>
                                </a>
                            @empty
                                @if ($supportAlerts->isEmpty())
                                <p class="px-3 py-3 text-muted small mb-0">{{ __('app.header.notifications_empty') }}</p>
                                @endif
                            @endforelse
                        </div>
                    </div>
                </div>

                <button class="icon-btn d-none d-sm-inline-flex" type="button" data-toggle="fullscreen" data-tip aria-label="{{ __('app.header.fullscreen') }}">
                    <i class="mdi mdi-fullscreen"></i>
                    <span class="ny-tip">{{ __('app.header.fullscreen') }}</span>
                </button>

                <div class="dropdown">
                    <button class="icon-btn user-menu-btn" type="button" data-bs-toggle="dropdown">
                        <span class="avatar avatar-init">{{ mb_substr($user->name, 0, 1) }}</span>
                        <span class="user-info">
                            <strong>{{ $user->name }}</strong>
                            <small>{{ $workspace?->name }}</small>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ $user->name }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('settings.account') }}" wire:navigate><i class="mdi mdi-account-outline me-2"></i>{{ __('app.nav.account') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('settings.account') }}" wire:navigate><i class="mdi mdi-cog me-2"></i>{{ __('app.nav.settings') }}</a></li>
                        @if ($isOwner && \App\Support\Edition::enabled('billing'))
                            <li><a class="dropdown-item" href="{{ route('settings.billing') }}" wire:navigate><i class="mdi mdi-credit-card-outline me-2"></i>{{ __('app.nav.billing') }}</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="mdi mdi-logout me-2"></i>{{ __('app.header.logout') }}</button>
                            </form>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </header>

    <nav class="h-nav">
        <a href="{{ route('dashboard') }}" class="h-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" wire:navigate>
            <i class="mdi mdi-view-dashboard-outline"></i>{{ __('app.nav.dashboard') }}
        </a>
        <a href="{{ route('clients') }}" class="h-nav-item {{ request()->routeIs('clients*') ? 'active' : '' }}" wire:navigate>
            <i class="mdi mdi-account-multiple-outline"></i>{{ __('app.nav.clients') }}
        </a>
        <a href="{{ route('assets') }}" class="h-nav-item {{ request()->routeIs('assets*') ? 'active' : '' }}" wire:navigate>
            <i class="mdi mdi-cube-outline"></i>{{ __('app.nav.assets') }}
        </a>
        <a href="{{ route('reports') }}" class="h-nav-item {{ request()->routeIs('reports*') ? 'active' : '' }}" wire:navigate>
            <i class="mdi mdi-file-chart-outline"></i>{{ __('app.nav.reports') }}
        </a>
        <a href="{{ route('notifications') }}" class="h-nav-item {{ request()->routeIs('notifications') ? 'active' : '' }}" wire:navigate>
            <i class="mdi mdi-bell-outline"></i>{{ __('app.nav.notifications') }}
        </a>
        <a href="{{ route('activity') }}" class="h-nav-item {{ request()->routeIs('activity') ? 'active' : '' }}" wire:navigate>
            <i class="mdi mdi-history"></i>{{ __('app.nav.activity') }}
        </a>
        <a href="{{ route('support') }}" class="h-nav-item {{ request()->routeIs('support*') ? 'active' : '' }}" wire:navigate>
            <i class="mdi mdi-lifebuoy"></i>{{ __('app.nav.support') }}
            @if ($unreadSupport > 0)
                <span class="badge badge-soft-danger ms-1">{{ $unreadSupport }}</span>
            @endif
        </a>
        <a href="{{ route('settings.account') }}" class="h-nav-item {{ request()->routeIs('settings') || request()->routeIs('settings.*') || request()->routeIs('import') || request()->routeIs('export') ? 'active' : '' }}" wire:navigate>
            <i class="mdi mdi-cog-outline"></i>{{ __('app.nav.settings') }}
        </a>
    </nav>

    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="brand" wire:navigate>
                <span class="brand-mark">D</span>
                <span class="brand-text">Duvento</span>
            </a>
        </div>
        <div class="sidebar-inner">
            <x-app-nav />
            @if ($isOwner && \App\Support\Edition::enabled('billing'))
                <div class="sidebar-promo">
                    <h6>{{ __('app.brand') }}</h6>
                    <p>{{ __('app.header.cloud_promo') }}</p>
                    <a href="{{ route('settings.billing') }}" class="btn btn-sm btn-light fw-bold" wire:navigate>{{ __('app.header.cloud_plans') }}</a>
                </div>
            @endif
        </div>
    </aside>
    <div class="backdrop"></div>
    <div class="content">
        <main class="page">
            @if (\App\Support\Impersonation::active())
                <div class="alert alert-warning d-flex align-items-center justify-content-between gap-2" role="alert">
                    <div>{{ __('app.header.impersonating', ['name' => $user->name]) }}</div>
                    <form method="POST" action="{{ route('impersonation.stop') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-dark">{{ __('app.header.stop_impersonation') }}</button>
                    </form>
                </div>
            @endif
            @if (\App\Support\Edition::isCloud() && $workspace?->trialExpired())
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="mdi mdi-alert-circle-outline fs-5"></i>
                    <div>{{ __('app.header.trial_ended') }}@if ($isOwner) <a href="{{ route('settings.billing') }}" class="alert-link" wire:navigate>{{ __('app.header.subscribe') }}</a>@endif</div>
                </div>
            @elseif (\App\Support\Edition::isCloud() && $workspace?->plan === \App\Enums\WorkspacePlan::FreeTrial && $workspace->trialEndsAt())
                <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                    <i class="mdi mdi-clock-outline fs-5"></i>
                    <div>{{ __('app.header.trial_until') }} <strong>{{ $workspace->trialEndsAt()->toDateString() }}</strong>.@if ($isOwner) <a href="{{ route('settings.billing') }}" class="alert-link" wire:navigate>{{ __('app.header.cloud_plans') }}</a>@endif</div>
                </div>
            @endif
            {{ $slot }}
        </main>
        <footer class="app-footer">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>© {{ date('Y') }} <strong class="text-gradient">{{ __('app.brand') }}</strong>. {{ __('app.footer_tagline') }}</div>
                <div class="d-flex gap-3">
                    <a href="{{ url('/') }}" class="text-muted small">{{ __('app.about') }}</a>
                    @if ($isOwner && \App\Support\Edition::enabled('billing'))
                        <a href="{{ route('settings.billing') }}" class="text-muted small" wire:navigate>{{ __('app.nav.billing') }}</a>
                    @endif
                </div>
            </div>
        </footer>
    </div>
</div>

<div
    id="ny-toasts"
    class="toast-container position-fixed bottom-0 end-0 p-3"
    @if (session('status'))
        data-flash-message="{{ session('status') }}"
        data-flash-type="{{ session('status_type', 'success') }}"
    @endif
></div>
<x-layouts.theme-foot />
</body>
</html>
