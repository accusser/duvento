@props([
    'livewire' => null,
])

@php
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $bsTheme = request()->cookie('nyvora-admin-theme') === 'dark' ? 'dark' : 'light';
    $isAuthed = filament()->auth()->check();

    // Mirrors the client-side layout preference so the shell is painted in its final
    // shape on the first frame instead of snapping once theme/js/app.js runs.
    $adminLayout = request()->cookie('nyvora-admin-layout');
    $adminLayout = in_array($adminLayout, ['boxed', 'boxed-mini', 'horizontal'], true) ? $adminLayout : 'boxed';
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}"
    data-bs-theme="{{ $bsTheme }}"
    data-ny-scope="admin"
    data-admin-path="{{ \App\Support\AdminPath::url() }}"
    @class([
        'fi',
        'dark' => $bsTheme === 'dark' || (filament()->hasDarkMode() && filament()->hasDarkModeForced()),
    ])
>
    <head>
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_START, scopes: $renderHookScopes) }}

        <meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        @if ($favicon = filament()->getFavicon())
            <link rel="icon" href="{{ $favicon }}" />
        @endif

        @php
            $title = trim(strip_tags($livewire?->getTitle() ?? ''));
            $brandName = trim(strip_tags(filament()->getBrandName()));
        @endphp

        <title>
            {{ filled($title) ? $title : null }}
            {{ filled($brandName) && filled($title) ? ' - ' : null }}
            {{ filled($brandName) ? $brandName : null }}
        </title>

        <style>
            html, body { background-color: #fff; }
            html[data-bs-theme="dark"], html[data-bs-theme="dark"] body { background-color: #2a2d2d; }
        </style>
        <script>
        (() => {
            const apply = () => {
                let cfg = {};
                try {
                    const parsed = JSON.parse(localStorage.getItem('nyvora-admin-config') || '{}');
                    cfg = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
                } catch (e) {}
                const configuredMode = cfg.mode || localStorage.getItem('theme') || 'light';
                let mode = ['light', 'dark', 'system'].includes(configuredMode) ? configuredMode : 'light';
                if (mode === 'system') {
                    mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', mode);
                document.documentElement.classList.toggle('dark', mode === 'dark');
                localStorage.setItem('theme', mode);
                const adminPath = document.documentElement.dataset.adminPath || '/admin';
                document.cookie = 'nyvora-admin-theme=' + mode + ';path=' + adminPath + ';max-age=31536000;SameSite=Lax';
                const layout = cfg.layout === 'mini' ? 'boxed' : (cfg.layout || 'boxed');
                const mini = !!(cfg.sidebarMini || cfg.layout === 'mini') && layout !== 'horizontal';
                const serverLayout = layout === 'horizontal' ? 'horizontal' : (mini ? 'boxed-mini' : 'boxed');
                document.cookie = 'nyvora-admin-layout=' + serverLayout + ';path=' + adminPath + ';max-age=31536000;SameSite=Lax';
                if (!document.body) return;
                const isApp = !!document.querySelector('.app');
                document.body.classList.toggle('sidebar-mini', mini && isApp);
                document.body.classList.toggle('layout-horizontal', layout === 'horizontal' && isApp);
                document.body.classList.toggle('layout-boxed', layout !== 'horizontal' && isApp);
            };
            apply();
            document.addEventListener('livewire:navigating', apply);
            document.addEventListener('livewire:navigated', apply);
            document.addEventListener('click', (e) => {
                if (e.target.closest('[data-toggle="theme"], [data-toggle="sidebar"]')) {
                    setTimeout(apply, 0);
                }
            });
        })();
        </script>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::STYLES_BEFORE, scopes: $renderHookScopes) }}

        <style>
            [x-cloak=''],
            [x-cloak='x-cloak'],
            [x-cloak='1'] {
                display: none !important;
            }

            [x-cloak='inline-flex'] {
                display: inline-flex !important;
            }

            @media (max-width: 1023px) {
                [x-cloak='-lg'] {
                    display: none !important;
                }
            }

            @media (min-width: 1024px) {
                [x-cloak='lg'] {
                    display: none !important;
                }
            }
        </style>

        @filamentStyles

        {{ filament()->getTheme()->getHtml() }}
        {{ filament()->getFontPreloadHtml() }}
        {{ filament()->getMonoFontPreloadHtml() }}
        {{ filament()->getSerifFontPreloadHtml() }}
        {{ filament()->getFontHtml() }}
        {{ filament()->getMonoFontHtml() }}
        {{ filament()->getSerifFontHtml() }}

        <style>
            :root {
                --font-family: '{!! filament()->getFontFamily() !!}';
                --mono-font-family: '{!! filament()->getMonoFontFamily() !!}';
                --serif-font-family: '{!! filament()->getSerifFontFamily() !!}';
                --sidebar-width: {{ filament()->getSidebarWidth() }};
                --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
                --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
            }

            html.fi {
                --livewire-progress-bar-color: var(--primary-500);
            }
        </style>

        @stack('styles')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::STYLES_AFTER, scopes: $renderHookScopes) }}

        @if (! filament()->hasDarkMode())
            <script>
                localStorage.setItem('theme', 'light')
            </script>
        @elseif (filament()->hasDarkModeForced())
            <script>
                localStorage.setItem('theme', 'dark')
            </script>
        @else
            <script>
                const loadDarkMode = () => {
                    let cfg = {};
                    try {
                        const parsed = JSON.parse(localStorage.getItem('nyvora-admin-config') || '{}');
                        cfg = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
                    } catch (e) {}
                    const configuredMode = cfg.mode || localStorage.getItem('theme') || @js(filament()->getDefaultThemeMode()->value);
                    window.theme = ['light', 'dark', 'system'].includes(configuredMode)
                        ? configuredMode
                        : @js(filament()->getDefaultThemeMode()->value);

                    const isDark =
                        window.theme === 'dark' ||
                        (window.theme === 'system' &&
                            window.matchMedia('(prefers-color-scheme: dark)')
                                .matches);

                    document.documentElement.classList.toggle('dark', isDark);
                    document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
                }

                loadDarkMode()

                document.addEventListener('livewire:navigated', loadDarkMode)
            </script>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_END, scopes: $renderHookScopes) }}
    </head>

    <body
        {{
            $attributes
                ->merge($livewire?->getExtraBodyAttributes() ?? [], escape: false)
                ->class([
                    'fi-body',
                    'fi-panel-' . filament()->getId(),
                    'layout-boxed' => $isAuthed && $adminLayout !== 'horizontal',
                    'sidebar-mini' => $isAuthed && $adminLayout === 'boxed-mini',
                    'layout-horizontal' => $isAuthed && $adminLayout === 'horizontal',
                ])
        }}
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_START, scopes: $renderHookScopes) }}

        {{ $slot }}

        @livewire(Filament\Livewire\Notifications::class)

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_BEFORE, scopes: $renderHookScopes) }}

        @filamentScripts(withCore: true)

        @if (filament()->hasBroadcasting() && config('filament.broadcasting.echo'))
            <script data-navigate-once>
                window.Echo = new window.EchoFactory(@js(config('filament.broadcasting.echo')))

                window.dispatchEvent(new CustomEvent('EchoLoaded'))
            </script>
        @endif

        @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
            <script>
                loadDarkMode()
            </script>
        @endif

        @stack('scripts')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_AFTER, scopes: $renderHookScopes) }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_END, scopes: $renderHookScopes) }}
    </body>
</html>
