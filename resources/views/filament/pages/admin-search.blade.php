<x-filament-panels::page>
    <div class="card mb-3">
        <div class="card-body">
            <input
                type="search"
                wire:model.live.debounce.300ms="q"
                class="form-control"
                placeholder="{{ __('admin.header.search') }}"
                aria-label="{{ __('admin.header.search') }}"
                autofocus
            >
        </div>
    </div>

    @if (mb_strlen(trim($q)) < \App\Support\AdminSearch::MIN_LENGTH)
        <p class="text-muted">{{ __('admin.search.hint') }}</p>
    @else
        @forelse ($groups as $group)
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ $group['label'] }}</h5></div>
                <div class="list-group list-group-flush">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" class="list-group-item list-group-item-action" wire:navigate>
                            <strong>{{ $item['title'] }}</strong>
                            @if ($item['subtitle'] !== '')
                                <span class="text-muted">· {{ $item['subtitle'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-muted">{{ __('admin.search.empty') }}</p>
        @endforelse
    @endif
</x-filament-panels::page>
