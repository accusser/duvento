<div class="max-w-xl">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Напоминания</h1>
    <p class="mt-1 mb-6 text-sm text-muted">За сколько дней до дедлайна слать email. Можно задать несколько порогов.</p>

    <form wire:submit="save" class="space-y-4">
        @foreach ($days as $index => $day)
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <x-ui.input label="Дней до истечения" type="number" min="1" wire:model="days.{{ $index }}" />
                </div>
                <x-ui.button type="button" wire:click="removeDay({{ $index }})">Убрать</x-ui.button>
            </div>
        @endforeach
        @error('days') <p class="text-sm text-critical">{{ $message }}</p> @enderror

        <div class="flex gap-2">
            <x-ui.button type="button" wire:click="addDay">Ещё порог</x-ui.button>
            <x-ui.button variant="accent" type="submit">Сохранить</x-ui.button>
        </div>
    </form>

    <p class="mt-8 text-sm text-muted">
        @if ($telegramEnabled)
            Telegram и Slack доступны в cloud-тарифе.
        @else
            Telegram и Slack — только в Cloud-версии.
        @endif
    </p>
</div>
