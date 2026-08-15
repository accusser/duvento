@props(['priority'])

@php
    $map = [
        'low' => ['ticket-badge-muted', 'mdi-arrow-down'],
        'normal' => ['ticket-badge-info', 'mdi-flag-outline'],
        'high' => ['ticket-badge-danger', 'mdi-fire'],
    ];
    [$tone, $icon] = $map[$priority->value] ?? $map['normal'];
@endphp

<span class="ticket-badge {{ $tone }}" @if ($priority->value === 'high') title="{{ __('app.support.urgent') }}" @endif>
    <i class="mdi {{ $icon }}"></i>{{ $priority->label() }}
</span>
