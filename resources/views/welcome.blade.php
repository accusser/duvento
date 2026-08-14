<x-layouts.guest>
    <div class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8">
        <header class="flex items-center justify-between">
            <p class="font-display text-xl font-semibold tracking-tight text-brand">Duvento</p>
            <div class="flex items-center gap-3">
                <span class="rounded-[10px] border border-border px-2.5 py-1 text-xs text-muted">{{ $edition }}</span>
                <x-theme-toggle />
            </div>
        </header>

        <main class="grid flex-1 items-center gap-12 py-16 lg:grid-cols-2">
            <div>
                <h1 class="font-display text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">
                    Ничего не сгорит незаметно
                </h1>
                <p class="mt-4 max-w-md text-muted">
                    Домены, SSL, хостинг и лицензии клиентов — в одном месте, с напоминаниями до дедлайна.
                </p>
                <div class="mt-8 flex gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex rounded-[10px] bg-accent px-4 py-2 text-sm font-medium text-white">В панель</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex rounded-[10px] border border-border px-4 py-2 text-sm">Вход</a>
                        <a href="{{ route('register') }}" class="inline-flex rounded-[10px] bg-accent px-4 py-2 text-sm font-medium text-white">Регистрация</a>
                    @endauth
                </div>
            </div>

            <div class="overflow-hidden rounded-[10px] border border-border bg-card">
                <div class="border-b border-border px-4 py-3 text-sm text-muted">Ближайшие дедлайны</div>
                <div class="divide-y divide-border">
                    @forelse ($assets as $asset)
                        <x-asset-countdown :asset="$asset" />
                    @empty
                        <p class="px-4 py-8 text-sm text-muted">Пока нет активов. Запустите <code class="font-mono">php artisan migrate --seed</code>.</p>
                    @endforelse
                </div>
            </div>
        </main>

        <section class="grid gap-6 border-t border-border py-12 md:grid-cols-3">
            <div class="rounded-[10px] border border-border bg-card p-5">
                <h2 class="font-display text-lg font-semibold">Self-host</h2>
                <p class="mt-2 font-mono text-2xl">$0</p>
                <p class="mt-2 text-sm text-muted">AGPLv3. Composer + MySQL + cron, без Docker.</p>
            </div>
            <div class="rounded-[10px] border border-border bg-card p-5">
                <h2 class="font-display text-lg font-semibold">Starter</h2>
                <p class="mt-2 font-mono text-2xl">$19<span class="text-sm text-muted">/мес</span></p>
                <p class="mt-2 text-sm text-muted">До 25 клиентов. Триал 14 дней, карта не нужна.</p>
            </div>
            <div class="rounded-[10px] border border-border bg-card p-5">
                <h2 class="font-display text-lg font-semibold">Agency</h2>
                <p class="mt-2 font-mono text-2xl">$49<span class="text-sm text-muted">/мес</span></p>
                <p class="mt-2 text-sm text-muted">До 100 клиентов и white-label отчёт клиенту.</p>
            </div>
        </section>

        <section class="grid gap-6 border-t border-border py-12 md:grid-cols-2">
            <div class="rounded-[10px] border border-border bg-card p-5">
                <h2 class="font-display text-lg font-semibold">Self-host</h2>
                <p class="mt-2 text-sm text-muted">AGPLv3, бесплатно. Composer + MySQL + cron, без Docker. Для r/selfhosted и тех, кто держит сервер сам.</p>
            </div>
            <div class="rounded-[10px] border border-border bg-card p-5">
                <h2 class="font-display text-lg font-semibold">Cloud</h2>
                <p class="mt-2 text-sm text-muted">Триал 14 дней, затем Starter $19 или Agency $49. Без возни с сервером — Indie Hackers / Product Hunt.</p>
                <form method="POST" action="{{ route('waitlist.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <x-ui.input name="name" placeholder="Имя" />
                    <x-ui.input name="email" type="email" placeholder="Email" required />
                    <x-ui.button variant="accent" type="submit">В вейтлист</x-ui.button>
                    @if (session('status'))
                        <p class="text-sm text-ok">{{ session('status') }}</p>
                    @endif
                </form>
            </div>
        </section>

        <footer class="border-t border-border py-6 text-sm text-muted">
            Self-host под AGPLv3. Cloud — тот же продукт без установки.
        </footer>
    </div>
</x-layouts.guest>
