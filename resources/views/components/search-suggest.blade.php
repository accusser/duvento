@props([
    'groups' => [],
    'emptyLabel' => '',
    'allUrl' => null,
    'allLabel' => '',
])

<div class="search-suggest" x-show="open" x-cloak>
    @forelse ($groups as $group)
        <div class="search-suggest-group">
            <div class="search-suggest-label">{{ $group['label'] }}</div>
            @foreach ($group['items'] as $item)
                <a href="{{ $item['url'] }}" class="search-suggest-item" wire:navigate x-on:click="open = false">
                    <span class="search-suggest-title">{{ $item['title'] }}</span>
                    @if (($item['subtitle'] ?? '') !== '')
                        <span class="search-suggest-sub">{{ $item['subtitle'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @empty
        <p class="search-suggest-empty">{{ $emptyLabel }}</p>
    @endforelse

    @if ($allUrl && $groups !== [])
        <a href="{{ $allUrl }}" class="search-suggest-all" wire:navigate x-on:click="open = false">{{ $allLabel }}</a>
    @endif
</div>
