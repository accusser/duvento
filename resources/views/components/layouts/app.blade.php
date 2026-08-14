<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <script>
        (() => {
            const stored = localStorage.getItem('theme');
            const theme = stored ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface text-ink antialiased" x-data="{ nav: false }">
    <div class="flex min-h-screen">
        <div
            x-show="nav"
            x-cloak
            class="fixed inset-0 z-20 bg-ink/40 md:hidden"
            @click="nav = false"
        ></div>
        <aside
            class="fixed inset-y-0 left-0 z-30 w-60 flex-col border-r border-border bg-card px-4 py-6 md:static md:flex"
            :class="nav ? 'flex' : 'hidden md:flex'"
        >
            <div class="flex items-center justify-between gap-2">
                <a href="{{ route('dashboard') }}" class="font-display text-xl font-semibold tracking-tight text-brand" wire:navigate>Duvento</a>
                <button type="button" class="text-sm text-muted md:hidden" @click="nav = false">Закрыть</button>
            </div>
            <p class="mt-1 truncate text-sm text-muted">{{ auth()->user()->currentWorkspace?->name }}</p>
            <div class="mt-8 flex flex-1 flex-col">
                <x-app-nav />
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <button type="submit" class="text-sm text-muted hover:text-ink">Выйти</button>
            </form>
        </aside>
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between gap-4 border-b border-border px-4 py-3 md:px-8">
                <button type="button" class="rounded-[10px] border border-border px-3 py-1.5 text-sm md:hidden" @click="nav = true">Меню</button>
                <p class="truncate text-sm text-muted md:hidden">{{ auth()->user()->currentWorkspace?->name }}</p>
                <div class="ml-auto flex items-center gap-3">
                    <span class="hidden text-sm text-muted sm:block">{{ auth()->user()->name }}</span>
                    <x-theme-toggle />
                </div>
            </header>
            <main class="flex-1 px-4 py-6 md:px-8">
                @php $workspace = auth()->user()->currentWorkspace; @endphp
                @if (\App\Support\Edition::isCloud() && $workspace?->trialExpired())
                    <p class="mb-4 rounded-[10px] border border-border px-4 py-3 text-sm">
                        Триал закончился.
                        <a href="{{ route('settings.billing') }}" class="text-brand" wire:navigate>Оформить подписку</a>
                    </p>
                @elseif (\App\Support\Edition::isCloud() && $workspace?->plan === \App\Enums\WorkspacePlan::FreeTrial && $workspace->trialEndsAt())
                    <p class="mb-4 text-sm text-muted">
                        Триал до <span class="font-mono">{{ $workspace->trialEndsAt()->toDateString() }}</span>.
                        <a href="{{ route('settings.billing') }}" class="text-brand" wire:navigate>Тарифы</a>
                    </p>
                @endif
                @if (session('status'))
                    <p class="mb-4 text-sm text-ok">{{ session('status') }}</p>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
