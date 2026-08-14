<div class="max-w-xl">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Типы активов</h1>
    <p class="mt-1 mb-6 text-sm text-muted">Системные типы общие для всех. Свои — только для этого воркспейса.</p>

    <h2 class="mb-2 text-sm text-muted">Системные</h2>
    <x-ui.card class="mb-6 divide-y divide-border overflow-hidden">
        @foreach ($system as $type)
            <p class="px-4 py-3">{{ $type->label }} <span class="font-mono text-xs text-muted">{{ $type->key }}</span></p>
        @endforeach
    </x-ui.card>

    <h2 class="mb-2 text-sm text-muted">Свои</h2>
    <form wire:submit="add" class="mb-4 flex items-end gap-2">
        <div class="flex-1">
            <x-ui.input label="Название" wire:model="label" placeholder="Например, страховка" />
        </div>
        <x-ui.button variant="accent" type="submit">Добавить</x-ui.button>
    </form>
    @error('label') <p class="mb-4 text-sm text-critical">{{ $message }}</p> @enderror

    <x-ui.card class="divide-y divide-border overflow-hidden">
        @forelse ($custom as $type)
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <p>{{ $type->label }} <span class="font-mono text-xs text-muted">{{ $type->key }}</span></p>
                <x-ui.button variant="danger" wire:click="delete({{ $type->id }})" wire:confirm="Удалить тип?">Удалить</x-ui.button>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-muted">Своих типов пока нет — можно добавить контракт, полис, SaaS-подписку.</p>
        @endforelse
    </x-ui.card>
</div>
