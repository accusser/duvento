<div
    class="topbar-search-wrap d-none d-md-block"
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape="open = false"
>
    <form action="{{ route('assets') }}" method="get" class="topbar-search" autocomplete="off">
        <i class="mdi mdi-magnify"></i>
        <input
            type="search"
            name="search"
            wire:model.live.debounce.300ms="q"
            x-on:focus="open = true"
            placeholder="{{ __('app.header.search_placeholder') }}"
            aria-label="{{ __('app.header.search') }}"
        >
    </form>

    @if (mb_strlen(trim($q)) >= \App\Support\WorkspaceSearch::MIN_LENGTH)
        <x-search-suggest
            :groups="$groups"
            :empty-label="__('app.header.search_empty')"
            :all-url="route('assets', ['search' => trim($q)])"
            :all-label="__('app.header.search_all')"
        />
    @endif
</div>
