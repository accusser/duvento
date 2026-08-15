<div
    class="topbar-search-wrap d-none d-md-block"
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape="open = false"
>
    <form action="{{ url(\App\Support\AdminPath::url('search')) }}" method="get" class="topbar-search" autocomplete="off">
        <i class="mdi mdi-magnify"></i>
        <input
            type="search"
            name="q"
            wire:model.live.debounce.300ms="q"
            x-on:focus="open = true"
            placeholder="{{ __('admin.header.search') }}"
            aria-label="{{ __('admin.header.search') }}"
        >
    </form>

    @if (mb_strlen(trim($q)) >= \App\Support\AdminSearch::MIN_LENGTH)
        <x-search-suggest
            :groups="$groups"
            :empty-label="__('admin.search.empty')"
            :all-url="url(\App\Support\AdminPath::url('search')).'?q='.urlencode(trim($q))"
            :all-label="__('admin.search.show_all')"
        />
    @endif
</div>
