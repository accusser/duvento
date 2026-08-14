<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold tracking-tight">Активы</h1>
            <p class="mt-1 text-sm text-muted">Всё, что истекает и стоит денег при пропуске</p>
        </div>
        <div class="flex gap-2">
            <x-ui.button href="{{ route('assets.export', request()->query()) }}">CSV</x-ui.button>
            <x-ui.button variant="accent" wire:click="create">Добавить</x-ui.button>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row">
        <div class="flex-1">
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Поиск" />
        </div>
        <x-ui.select wire:model.live="status">
            <option value="">Все статусы</option>
            <option value="critical">Critical</option>
            <option value="urgent">Urgent</option>
            <option value="upcoming">Upcoming</option>
            <option value="ok">OK</option>
            <option value="unknown">Unknown</option>
        </x-ui.select>
        <x-ui.select wire:model.live="clientId">
            <option value="">Все клиенты</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}">{{ $client->name }}</option>
            @endforeach
        </x-ui.select>
    </div>

    <x-ui.card class="table-fade overflow-hidden">
        @forelse ($assets as $asset)
            <div class="flex items-stretch border-b border-border last:border-b-0">
                <x-asset-countdown :asset="$asset" class="min-w-0 flex-1" />
                <div class="flex items-center gap-2 pr-4">
                    <x-ui.button wire:click="edit({{ $asset->id }})">Изменить</x-ui.button>
                    <x-ui.button variant="danger" wire:click="delete({{ $asset->id }})" wire:confirm="Удалить актив?">Удалить</x-ui.button>
                </div>
            </div>
        @empty
            <p class="px-4 py-8 text-sm text-muted">Активов пока нет.</p>
        @endforelse
    </x-ui.card>

    <x-ui.modal :open="$showForm" :title="$editingId ? 'Актив' : 'Новый актив'">
        <form wire:submit="save" class="space-y-4">
            <x-ui.select label="Клиент" wire:model="formClientId">
                <option value="">Выберите</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </x-ui.select>
            @error('formClientId') <p class="text-sm text-critical">{{ $message }}</p> @enderror

            <x-ui.select label="Тип" wire:model="assetTypeId">
                <option value="">Выберите</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}">{{ $type->label }}{{ $type->isSystem() ? '' : ' (свой)' }}</option>
                @endforeach
            </x-ui.select>
            @error('assetTypeId') <p class="text-sm text-critical">{{ $message }}</p> @enderror

            <x-ui.input label="Название / хост" wire:model="name" />
            @error('name') <p class="text-sm text-critical">{{ $message }}</p> @enderror

            <x-ui.input label="Истекает" type="date" wire:model="expiresAt" />

            <div class="grid gap-3 sm:grid-cols-3">
                <x-ui.select label="Автопродление" wire:model="autoRenew">
                    <option value="yes">Да</option>
                    <option value="no">Нет</option>
                    <option value="unknown">Неизвестно</option>
                </x-ui.select>
                <x-ui.select label="Владелец" wire:model="owner">
                    <option value="agency">Агентство</option>
                    <option value="client">Клиент</option>
                    <option value="unknown">Неизвестно</option>
                </x-ui.select>
                <x-ui.select label="Плательщик" wire:model="payer">
                    <option value="agency">Агентство</option>
                    <option value="client">Клиент</option>
                    <option value="unknown">Неизвестно</option>
                </x-ui.select>
            </div>

            <x-ui.input label="Email для напоминаний" type="email" wire:model="noticeEmail" />
            <x-ui.textarea label="Заметки" wire:model="notes" rows="2" />

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="sslCheckEnabled" class="size-4 rounded border-border">
                Автопроверка SSL (порт 443)
            </label>

            <x-ui.input label="Свои напоминания (дни через запятую, пусто = правила воркспейса)" wire:model="overrideDays" placeholder="30, 14, 7" />

            <div class="flex justify-end gap-2">
                <x-ui.button type="button" wire:click="close">Отмена</x-ui.button>
                @if ($editingId)
                    <x-ui.button type="button" wire:click="markRenewedFromForm">Продлено</x-ui.button>
                @endif
                <x-ui.button variant="accent" type="submit">Сохранить</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
