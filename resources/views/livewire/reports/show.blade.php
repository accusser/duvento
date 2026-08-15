@php
    $stats = [
        ['key' => 'total', 'label' => __('app.reports.stat_total')],
        ['key' => 'expired', 'label' => __('app.reports.stat_expired')],
        ['key' => 'critical', 'label' => __('app.reports.stat_critical')],
        ['key' => 'month', 'label' => __('app.reports.stat_month')],
        ['key' => 'unknown_date', 'label' => __('app.reports.stat_unknown_date')],
        ['key' => 'unknown_owner', 'label' => __('app.reports.stat_unknown_owner')],
        ['key' => 'unknown_payer', 'label' => __('app.reports.stat_unknown_payer')],
    ];
    $contact = collect([$client->contact_name, $client->email])->filter()->implode(' · ') ?: __('app.reports.no_contact');
@endphp

<style>
    @media print {
        .topbar, .h-nav, .sidebar, .report-print-hide { display: none !important; }
        .content, .content-page, .page-content, .app { margin: 0 !important; padding: 0 !important; }
    }
</style>

<div>
    <a href="{{ route('reports') }}" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1 mb-3 report-print-hide" wire:navigate>
        <i class="mdi mdi-arrow-left"></i> {{ __('app.reports.back') }}
    </a>

    <x-page-head :title="__('app.reports.show_title')" :sub="__('app.reports.show_sub')">
        <x-ui.button href="{{ route('export.assets', ['clientId' => $client->id]) }}" class="report-print-hide">{{ __('app.reports.csv') }}</x-ui.button>
        <x-ui.button type="button" class="report-print-hide" onclick="window.print()">{{ __('app.reports.print') }}</x-ui.button>
        <x-ui.button variant="accent" href="{{ route('clients.show', $client) }}" class="report-print-hide" wire:navigate>{{ __('app.reports.open_client') }}</x-ui.button>
    </x-page-head>

    <p class="text-muted small mb-3">
        {{ __('app.reports.meta_client', ['name' => $client->name]) }}
        · {{ __('app.reports.meta_date', ['date' => now()->translatedFormat('d M Y')]) }}
        · {{ __('app.reports.meta_contact', ['contact' => $contact]) }}
    </p>

    <div class="row g-3 mb-3">
        @foreach ($stats as $stat)
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="stat-label">{{ $stat['label'] }}</div>
                        <div class="fw-semibold {{ $counts[$stat['key']] === 0 ? 'text-muted' : '' }}">{{ $counts[$stat['key']] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('app.reports.risks') }}</h5></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.table.asset') }}</th>
                        <th>{{ __('app.table.type') }}</th>
                        <th>{{ __('app.table.expires') }}</th>
                        <th>{{ __('app.table.days') }}</th>
                        <th>{{ __('app.table.status') }}</th>
                        <th>{{ __('app.table.renews') }}</th>
                        <th>{{ __('app.table.pays') }}</th>
                        <th>{{ __('app.reports.recommended') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        @php($rec = \App\Support\ClientReport::recommendation($asset))
                        <tr class="{{ $asset->status->borderClass() }}">
                            <td>
                                <a href="{{ route('assets.show', $asset) }}" class="fw-semibold text-decoration-none" wire:navigate>{{ $asset->name }}</a>
                            </td>
                            <td class="text-muted">{{ $asset->assetType?->displayLabel() }}</td>
                            <td><code>{{ $asset->expires_at?->toDateString() ?? __('app.common.empty') }}</code></td>
                            <td>{{ $asset->days_left === null ? __('app.common.empty') : $asset->days_left }}</td>
                            <td>
                                <span class="{{ $asset->status->badgeClass() }}">{{ $asset->status->label() }}</span>
                            </td>
                            <td class="text-muted">{{ $asset->owner->label() }}</td>
                            <td class="text-muted">{{ $asset->payer->label() }}</td>
                            <td>
                                @if ($rec)
                                    <a href="{{ $rec === 'renew' ? route('assets.show', $asset) : route('assets.edit', $asset) }}" class="small" wire:navigate>
                                        {{ __('app.reports.rec_'.$rec) }}
                                    </a>
                                @else
                                    {{ __('app.common.empty') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="ny-list-empty">{{ __('app.reports.no_assets') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('app.reports.actions') }}</h5></div>
        <div class="list-group list-group-flush">
            @forelse ($actions as $i => $item)
                <a href="{{ $item['key'] === 'renew' ? route('assets.show', $item['asset']) : route('assets.edit', $item['asset']) }}" class="list-group-item list-group-item-action" wire:navigate>
                    {{ $i + 1 }}. {{ __('app.reports.action_'.$item['key'], ['name' => $item['asset']->name]) }}
                </a>
            @empty
                <div class="ny-list-empty">{{ __('app.reports.actions_empty') }}</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('app.reports.history') }}</h5></div>
        <div class="list-group list-group-flush">
            @forelse ($logs as $log)
                <div class="list-group-item">
                    <div class="fw-semibold">{{ __('app.activity.actions.'.$log->action) }}</div>
                    <div class="small text-muted">
                        <code>{{ $log->created_at->format('Y-m-d H:i') }}</code>
                        · {{ $log->user?->name ?? __('app.common.system') }}
                        @if (! empty($log->properties['name']))
                            · {{ $log->properties['name'] }}
                        @endif
                        @if (! empty($log->properties['email']))
                            · {{ $log->properties['email'] }}
                        @endif
                        @if (! empty($log->properties['from']) || ! empty($log->properties['to']))
                            · {{ $log->properties['from'] ?? '—' }} → {{ $log->properties['to'] ?? '—' }}
                        @endif
                    </div>
                </div>
            @empty
                <div class="ny-list-empty">{{ __('app.reports.history_empty') }}</div>
            @endforelse
        </div>
    </div>
</div>
