<div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6 py-12">
    <a href="/" class="font-display text-2xl font-semibold tracking-tight text-brand">Duvento</a>
    <h1 class="mt-8 font-display text-2xl font-semibold">Вход</h1>
    <form wire:submit="authenticate" class="mt-6 space-y-4">
        <x-ui.input label="Email" type="email" wire:model="email" autocomplete="username" />
        @error('email') <p class="text-sm text-critical">{{ $message }}</p> @enderror
        <x-ui.input label="Пароль" type="password" wire:model="password" autocomplete="current-password" />
        @error('password') <p class="text-sm text-critical">{{ $message }}</p> @enderror
        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" wire:model="remember" class="size-4 rounded border-border">
            Запомнить
        </label>
        <x-ui.button variant="accent" type="submit" class="w-full">Войти</x-ui.button>
    </form>
    <p class="mt-6 text-sm text-muted">
        Нет аккаунта? <a href="{{ route('register') }}" class="text-brand" wire:navigate>Регистрация</a>
    </p>
</div>
