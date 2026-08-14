@php
    $items = [
        ['route' => 'dashboard', 'label' => 'Дашборд', 'match' => 'dashboard'],
        ['route' => 'clients', 'label' => 'Клиенты', 'match' => 'clients'],
        ['route' => 'assets', 'label' => 'Активы', 'match' => 'assets'],
        ['route' => 'activity', 'label' => 'Журнал', 'match' => 'activity'],
        ['route' => 'settings.reminders', 'label' => 'Напоминания', 'match' => 'settings.reminders'],
        ['route' => 'settings.types', 'label' => 'Типы активов', 'match' => 'settings.types'],
    ];
@endphp

<nav class="flex flex-1 flex-col gap-1 text-sm">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" class="rounded-[10px] px-3 py-2 {{ request()->routeIs($item['match']) ? 'bg-surface text-ink' : 'text-muted hover:text-ink' }}" wire:navigate>{{ $item['label'] }}</a>
    @endforeach
    @if (\App\Support\Edition::enabled('billing'))
        <a href="{{ route('settings.billing') }}" class="rounded-[10px] px-3 py-2 {{ request()->routeIs('settings.billing') ? 'bg-surface text-ink' : 'text-muted hover:text-ink' }}" wire:navigate>Тариф</a>
    @endif
    @if (\App\Support\Edition::enabled('white_label', auth()->user()->currentWorkspace))
        <a href="{{ route('reports.clients') }}" class="rounded-[10px] px-3 py-2 text-muted hover:text-ink">Отчёт клиенту</a>
    @endif
</nav>
