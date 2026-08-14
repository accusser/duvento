@php
    $labels = [
        'critical' => 'Critical',
        'urgent' => 'Urgent',
        'upcoming' => 'Upcoming',
        'ok' => 'OK',
        'unknown' => 'Unknown',
    ];
    $colors = [
        'critical' => 'bg-critical',
        'urgent' => 'bg-urgent',
        'upcoming' => 'bg-upcoming',
        'ok' => 'bg-ok',
        'unknown' => 'bg-muted',
    ];
@endphp

<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold tracking-tight">Дашборд</h1>
            <p class="mt-1 text-sm text-muted">Спокойный обзор сроков по всем активам</p>
        </div>
        <x-ui.button variant="ghost" href="{{ route('assets.export', request()->query()) }}">CSV</x-ui.button>
    </div>

    <div class="mb-6 flex h-2 overflow-hidden rounded-full border border-border" aria-hidden="true">
        @foreach (['ok', 'upcoming', 'urgent', 'critical'] as $key)
            @if ($counts[$key] > 0)
                <span class="{{ $colors[$key] }} h-full" style="width: {{ ($counts[$key] / $pulseTotal) * 100 }}%"></span>
            @endif
        @endforeach
    </div>

    <div class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($statusKeys as $key)
            <button
                type="button"
                wire:click="filterStatus('{{ $key }}')"
                class="card-hover rounded-[10px] border border-border bg-card px-4 py-3 text-left {{ $status === $key ? 'border-brand' : '' }}"
            >
                <p class="text-sm text-muted">{{ $labels[$key] }}</p>
                <p class="mt-1 font-mono text-3xl leading-none">{{ $counts[$key] }}</p>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row">
        <div class="flex-1">
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Поиск по активу или клиенту" />
        </div>
        <x-ui.select wire:model.live="clientId">
            <option value="">Все клиенты</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}">{{ $client->name }}</option>
            @endforeach
        </x-ui.select>
    </div>

    <x-ui.card class="table-fade overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="border-b border-border text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Дней</th>
                    <th class="px-4 py-3 font-medium">Актив</th>
                    <th class="px-4 py-3 font-medium">Клиент</th>
                    <th class="px-4 py-3 font-medium">Тип</th>
                    <th class="px-4 py-3 font-medium">Истекает</th>
                    <th class="px-4 py-3 font-medium">Статус</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assets as $asset)
                    <tr class="border-b border-border border-l-[3px] last:border-b-0 {{ $asset->status->borderClass() }}">
                        <td class="px-4 py-3 font-mono text-2xl leading-none">{{ $asset->days_left === null ? '—' : $asset->days_left }}</td>
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('assets', ['search' => $asset->name]) }}" class="hover:text-brand" wire:navigate>{{ $asset->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $asset->client?->name }}</td>
                        <td class="px-4 py-3 text-muted">{{ $asset->assetType?->label }}</td>
                        <td class="px-4 py-3 font-mono">{{ $asset->expires_at?->toDateString() ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2">
                                <span class="{{ $asset->status->dotClass() }} size-2 rounded-full" aria-hidden="true"></span>
                                {{ $asset->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-muted">Нет активов по текущим фильтрам.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.card>
</div>
