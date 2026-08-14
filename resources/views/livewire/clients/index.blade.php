<div>
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold tracking-tight">Клиенты</h1>
            <p class="mt-1 text-sm text-muted">Агентства и заказчики этого воркспейса</p>
        </div>
        <x-ui.button variant="accent" wire:click="create">Добавить</x-ui.button>
    </div>

    <div class="mb-4 max-w-md">
        <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Поиск по имени или email" />
    </div>

    <x-ui.card class="table-fade divide-y divide-border overflow-hidden">
        @forelse ($clients as $client)
            <div class="flex items-center gap-4 px-4 py-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ $client->name }}</p>
                    <p class="truncate text-sm text-muted">{{ $client->email ?: 'без email' }} · {{ $client->assets_count }} активов</p>
                </div>
                <x-ui.button wire:click="edit({{ $client->id }})">Изменить</x-ui.button>
                <x-ui.button variant="danger" wire:click="delete({{ $client->id }})" wire:confirm="Удалить клиента и его активы?">Удалить</x-ui.button>
            </div>
        @empty
            <p class="px-4 py-8 text-sm text-muted">Клиентов пока нет.</p>
        @endforelse
    </x-ui.card>

    <x-ui.modal :open="$showForm" :title="$editingId ? 'Клиент' : 'Новый клиент'">
        <form wire:submit="save" class="space-y-4">
            <x-ui.input label="Имя" wire:model="name" />
            @error('name') <p class="text-sm text-critical">{{ $message }}</p> @enderror
            <x-ui.input label="Email" type="email" wire:model="email" />
            @error('email') <p class="text-sm text-critical">{{ $message }}</p> @enderror
            <x-ui.textarea label="Заметки" wire:model="notes" rows="3" />
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" wire:click="close">Отмена</x-ui.button>
                <x-ui.button variant="accent" type="submit">Сохранить</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
