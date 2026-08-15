@props(['asset'])

<article {{ $attributes->class(['d-flex align-items-center gap-3 px-3 py-3', $asset->status->borderClass()]) }}>
    <span class="{{ $asset->status->dotClass() }}" aria-hidden="true"></span>
    <div class="min-w-0 flex-grow-1">
        <p class="mb-0 fw-semibold text-truncate">{{ $asset->name }}</p>
        <p class="mb-0 small text-muted text-truncate">
            {{ $asset->client?->name }} · {{ $asset->assetType?->displayLabel() }}
        </p>
    </div>
    <div class="text-end flex-shrink-0">
        <p class="countdown-days mb-0">{{ $asset->days_left === null ? '—' : $asset->days_left }}</p>
        <p class="mb-0 small text-muted">{{ $asset->status->label() }}</p>
    </div>
</article>
