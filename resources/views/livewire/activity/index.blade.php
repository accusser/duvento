@php
    $labels = [
        'asset.created' => 'Актив создан',
        'asset.updated' => 'Актив изменён',
        'asset.date_changed' => 'Дата истечения изменена',
        'asset.renewed' => 'Отмечено продление',
        'asset.deleted' => 'Актив удалён',
        'client.created' => 'Клиент создан',
        'client.updated' => 'Клиент изменён',
        'client.deleted' => 'Клиент удалён',
        'reminder.sent' => 'Напоминание отправлено',
        'ssl.updated' => 'SSL-дата обновлена',
        'ssl.check_failed' => 'SSL-проверка не удалась',
        'reminders.updated' => 'Правила напоминаний обновлены',
        'asset_type.created' => 'Тип актива создан',
        'asset_type.deleted' => 'Тип актива удалён',
    ];
@endphp

<div>
    <h1 class="mb-6 font-display text-2xl font-semibold tracking-tight">Журнал</h1>
    <x-ui.card class="table-fade divide-y divide-border overflow-hidden">
        @forelse ($logs as $log)
            <div class="px-4 py-3">
                <p class="font-medium">{{ $labels[$log->action] ?? $log->action }}</p>
                <p class="mt-1 font-mono text-sm text-muted">
                    {{ $log->created_at->format('Y-m-d H:i') }}
                    · {{ $log->user?->name ?? 'система' }}
                    @if (!empty($log->properties['name']))
                        · {{ $log->properties['name'] }}
                    @endif
                    @if (!empty($log->properties['email']))
                        · {{ $log->properties['email'] }}
                    @endif
                </p>
            </div>
        @empty
            <p class="px-4 py-8 text-sm text-muted">Пока пусто.</p>
        @endforelse
    </x-ui.card>
    <div class="mt-4">{{ $logs->links() }}</div>
</div>
