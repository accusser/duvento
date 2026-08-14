@props(['asset'])

@php
    $status = $asset->status;
    $days = $asset->days_left;
    $bar = match ($status->colorToken()) {
        'ok' => 'border-ok',
        'upcoming' => 'border-upcoming',
        'urgent' => 'border-urgent',
        'critical' => 'border-critical',
        default => 'border-muted',
    };
    $dot = match ($status->colorToken()) {
        'ok' => 'bg-ok',
        'upcoming' => 'bg-upcoming',
        'urgent' => 'bg-urgent',
        'critical' => 'bg-critical',
        default => 'bg-muted',
    };
@endphp

<article {{ $attributes->class(['flex items-center gap-4 border-l-[3px] bg-surface px-4 py-3', $bar]) }}>
    <span class="{{ $dot }} size-2 shrink-0 rounded-full" aria-hidden="true"></span>
    <div class="min-w-0 flex-1">
        <p class="truncate font-medium">{{ $asset->name }}</p>
        <p class="truncate text-sm text-muted">
            {{ $asset->client?->name }} · {{ $asset->assetType?->label }}
        </p>
    </div>
    <div class="shrink-0 text-right">
        <p class="font-mono text-2xl leading-none tracking-tight">
            {{ $days === null ? '—' : $days }}
        </p>
        <p class="mt-1 text-xs text-muted">{{ $status->label() }}</p>
    </div>
</article>
