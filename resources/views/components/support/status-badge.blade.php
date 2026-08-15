@props(['status'])

@php
    $map = [
        'open' => ['ticket-badge-success', 'mdi-email-open-outline'],
        'in_progress' => ['ticket-badge-warning', 'mdi-autorenew'],
        'closed' => ['ticket-badge-muted', 'mdi-check-circle-outline'],
    ];
    [$tone, $icon] = $map[$status->value] ?? $map['open'];
@endphp

<span class="ticket-badge {{ $tone }}">
    <i class="mdi {{ $icon }}"></i>{{ $status->label() }}
</span>
