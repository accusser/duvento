<div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6 py-12">
    <a href="/" class="font-display text-2xl font-semibold tracking-tight text-brand">Duvento</a>
    <h1 class="mt-8 font-display text-2xl font-semibold">Регистрация</h1>
    @if (\App\Support\Edition::isCloud())
        <p class="mt-2 text-sm text-muted">14 дней триала, карта не нужна. Потом Starter $19 или Agency $49.</p>
    @endif
    <form wire:submit="register" class="mt-6 space-y-4">
        <x-ui.input label="Имя" wire:model="name" />
        @error('name') <p class="text-sm text-critical">{{ $message }}</p> @enderror
        <x-ui.input label="Email" type="email" wire:model="email" autocomplete="username" />
        @error('email') <p class="text-sm text-critical">{{ $message }}</p> @enderror
        <x-ui.input label="Агентство / воркспейс" wire:model="workspace" />
        @error('workspace') <p class="text-sm text-critical">{{ $message }}</p> @enderror
        <x-ui.input label="Пароль" type="password" wire:model="password" autocomplete="new-password" />
        @error('password') <p class="text-sm text-critical">{{ $message }}</p> @enderror
        <x-ui.input label="Пароль ещё раз" type="password" wire:model="password_confirmation" autocomplete="new-password" />
        <x-ui.button variant="accent" type="submit" class="w-full">Создать аккаунт</x-ui.button>
    </form>
    <p class="mt-6 text-sm text-muted">
        Уже есть аккаунт? <a href="{{ route('login') }}" class="text-brand" wire:navigate>Вход</a>
    </p>
</div>
