@php
    $livewire ??= null;
    $renderHookScopes = $livewire?->getRenderHookScopes();
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <a href="#fi-main-content" class="fi-skip-link fi-sr-only">
        {{ __('filament-panels::layout.skip_to_content.label') }}
    </a>

    <x-admin-shell>
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_BEFORE, scopes: $renderHookScopes) }}

        <div
            id="fi-main-content"
            tabindex="-1"
            class="fi-main"
        >
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, scopes: $renderHookScopes) }}
            {{ $slot }}
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_END, scopes: $renderHookScopes) }}
        </div>
    </x-admin-shell>
</x-filament-panels::layout.base>
