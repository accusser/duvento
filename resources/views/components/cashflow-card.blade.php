@props([
    'summary',
    'days',
    'open' => false,
    'toggle' => false,
])

@php
    $hasAmount = $summary['count'] > 0;
@endphp

<div class="card h-100 {{ $open ? 'is-active' : '' }}">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div class="stat-label mb-0">{{ __('app.cashflow.title') }}</div>
            <div class="cashflow-periods" @if ($toggle) wire:click.stop @endif>
                @foreach (\App\Support\UpcomingPayments::PERIODS as $period)
                    <button
                        type="button"
                        class="btn btn-sm {{ (int) $days === $period ? 'btn-primary' : 'btn-outline-secondary' }}"
                        wire:click="setCashflowDays({{ $period }})"
                    >{{ $period }}</button>
                @endforeach
            </div>
        </div>

        <{{ $toggle ? 'button' : 'div' }}
            @if ($toggle) type="button" class="cashflow-toggle" wire:click="toggleCashflow" @endif
        >
            <div class="cashflow-amount">
                @if ($hasAmount)
                    @foreach ($summary['by_currency'] as $code => $amount)
                        <div>{{ \App\Support\UpcomingPayments::format($code, $amount) }}</div>
                    @endforeach
                @else
                    <div>{{ __('app.common.empty') }}</div>
                @endif
            </div>
            <div class="small text-muted mt-2">
                @if ($hasAmount)
                    {{ trans_choice('app.cashflow.caption', $summary['count'], [
                        'days' => $summary['days'],
                        'amount' => $summary['total_label'],
                        'count' => $summary['count'],
                    ]) }}
                @else
                    {{ __('app.cashflow.caption_empty', ['days' => $summary['days']]) }}
                @endif
            </div>
            @if ($hasAmount)
                <div class="small mt-2 d-flex flex-wrap gap-2">
                    @if ($summary['payer_labels']['agency'] !== '')
                        <span>{{ __('app.cashflow.agency_pays', ['amount' => $summary['payer_labels']['agency']]) }}</span>
                    @endif
                    @if ($summary['payer_labels']['client'] !== '')
                        <span>{{ __('app.cashflow.client_pays', ['amount' => $summary['payer_labels']['client']]) }}</span>
                    @endif
                    @if ($summary['payer_labels']['unknown'] !== '')
                        <span class="text-muted">{{ __('app.cashflow.unknown_pays', ['amount' => $summary['payer_labels']['unknown']]) }}</span>
                    @endif
                </div>
            @else
                <div class="small text-muted mt-2">{{ __('app.cashflow.empty_hint') }}</div>
            @endif
        </{{ $toggle ? 'button' : 'div' }}>

        <div class="cashflow-trend" wire:click.stop title="{{ __('app.cashflow.trend') }}">
            @foreach ($summary['trend']['months'] as $month)
                <div class="cashflow-trend-col" title="{{ $month['label'] }}{{ $month['total_label'] !== '' ? ': '.$month['total_label'] : '' }}">
                    <div class="cashflow-trend-bars">
                        @forelse ($month['bars'] as $bar)
                            <span class="cashflow-trend-bar" style="height: {{ $bar['height'] }}%"></span>
                        @empty
                            <span class="cashflow-trend-bar is-empty"></span>
                        @endforelse
                    </div>
                    <div class="cashflow-trend-label">{{ $month['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
