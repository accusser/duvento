<x-filament-widgets::widget class="fi-wi-ny-stats">
@if ($alerts !== [])
    <div class="alert alert-warning d-flex align-items-start gap-3 mb-3">
        <i class="mdi mdi-alert-outline fs-4"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ __('admin.health.banner') }}</div>
            <ul class="mb-0 mt-1 small">
                @foreach ($alerts as $alert)
                    <li>{{ $alert['label'] }} — {{ ($alert['level'] ?? '') === 'warn' ? __('admin.health.warn') : __('admin.health.stale') }}</li>
                @endforeach
            </ul>
        </div>
        <a href="{{ $healthUrl }}" class="btn btn-sm btn-warning flex-shrink-0" wire:navigate>
            {{ __('admin.health.open') }}
        </a>
        <button
            type="button"
            class="btn-close flex-shrink-0"
            wire:click="dismissHealthAlerts"
            title="{{ __('admin.health.hide') }}"
            aria-label="{{ __('admin.health.hide') }}"
        ></button>
    </div>
@endif
<div class="row g-3">
    @foreach ($stats as $stat)
        <div class="col-6 col-xl">
            <a
                href="{{ $stat['url'] }}"
                class="card card-ribbon stat-card-link {{ $stat['ribbon'] }} h-100"
                wire:navigate
                aria-label="{{ $stat['label'] }}: {{ $stat['value'] }}"
            >
                <div class="stat-tile">
                    <div class="stat-head">
                        <div class="stat-label">{{ $stat['label'] }}</div>
                        <div class="stat-icon-wrap {{ $stat['iconWrap'] }}">
                            <i class="mdi {{ $stat['mdi'] }}"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ $stat['value'] }}</div>
                    <div class="stat-foot">
                        <span @class(['stat-delta', 'up' => $stat['up'], 'down' => ! $stat['up']])>
                            <i class="mdi {{ $stat['up'] ? 'mdi-trending-up' : 'mdi-trending-down' }}"></i>
                            {{ $stat['percent'] }}%
                        </span>
                        <span class="stat-extra">{{ $stat['caption'] }}</span>
                    </div>
                    <div class="stat-corner"></div>
                </div>
            </a>
        </div>
    @endforeach
</div>
</x-filament-widgets::widget>
