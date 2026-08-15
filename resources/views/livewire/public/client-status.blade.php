@php
    $total = $assets->count();
    $attention = $assets->filter(fn ($asset) => $asset->status->rank() >= 3)->count();
    $ok = $assets->filter(fn ($asset) => $asset->status === \App\Enums\AssetStatus::Ok)->count();
    $segments = $assets->countBy(fn ($asset) => $asset->status->dashboardKey());
    $typeIcon = fn (?string $icon) => match ($icon) {
        'globe' => 'mdi-web',
        'lock' => 'mdi-lock-outline',
        'server' => 'mdi-server',
        'puzzle' => 'mdi-puzzle-outline',
        default => 'mdi-cube-outline',
    };
@endphp

<div class="share-page">
    <div class="share-page-inner">
        <header class="share-head">
            <div class="share-brand">
                <span class="brand-mark">D</span>
                <div>
                    <div class="share-kicker">{{ __('app.share.provided', ['agency' => $agency]) }}</div>
                    <h1 class="share-client">{{ $clientName }}</h1>
                    <div class="share-asof">{{ __('app.share.as_of', ['date' => now()->toDateString()]) }}</div>
                </div>
            </div>
        </header>

        @if ($total > 0)
            <div class="share-stats">
                <div class="share-stat">
                    <div class="share-stat-value">{{ $total }}</div>
                    <div class="share-stat-label">{{ __('app.share.total') }}</div>
                </div>
                <div class="share-stat {{ $attention > 0 ? 'is-alert' : 'is-ok' }}">
                    <div class="share-stat-value">{{ $attention }}</div>
                    <div class="share-stat-label">{{ __('app.share.attention') }}</div>
                </div>
                <div class="share-stat">
                    <div class="share-stat-value">{{ $ok }}</div>
                    <div class="share-stat-label">{{ __('app.enums.status.ok') }}</div>
                </div>
            </div>
            <div class="progress-pulse share-pulse" aria-hidden="true">
                @foreach (['critical' => 'seg-critical', 'urgent' => 'seg-urgent', 'upcoming' => 'seg-upcoming', 'ok' => 'seg-ok', 'unknown' => 'seg-ok'] as $key => $seg)
                    @if (($segments[$key] ?? 0) > 0)
                        <span class="{{ $seg }}" style="width: {{ round(100 * $segments[$key] / $total, 2) }}%"></span>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="card share-card">
            @forelse ($assets as $asset)
                <div class="share-row {{ $asset->status->borderClass() }}">
                    <div class="share-days-wrap">
                        <span class="share-days">{{ $asset->days_left === null ? __('app.common.empty') : $asset->days_left }}</span>
                        <span class="share-days-unit">{{ __('app.table.days') }}</span>
                    </div>
                    <div class="share-asset">
                        <div class="share-name">
                            <i class="mdi {{ $typeIcon($asset->assetType?->icon) }}"></i>
                            {{ $asset->name }}
                        </div>
                        <div class="share-meta">
                            {{ $asset->assetType?->displayLabel() }}
                            @if ($asset->expires_at)
                                · {{ $asset->expires_at->toDateString() }}
                            @endif
                        </div>
                    </div>
                    <span class="{{ $asset->status->badgeClass() }} share-badge">
                        <span class="{{ $asset->status->dotClass() }} me-1"></span>
                        {{ $asset->status->label() }}
                    </span>
                </div>
            @empty
                <div class="share-empty">
                    <i class="mdi mdi-calendar-blank-outline"></i>
                    <div>{{ __('app.share.empty') }}</div>
                </div>
            @endforelse
        </div>

        <footer class="share-foot">{{ __('app.brand') }}</footer>
    </div>
</div>
