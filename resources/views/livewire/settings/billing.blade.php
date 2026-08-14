<div class="max-w-xl">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Тариф</h1>
    <p class="mt-1 mb-6 text-sm text-muted">Cloud: триал 14 дней, затем Starter или Agency. Без ключа Paddle работает sandbox.</p>

    <x-ui.card class="space-y-3 p-4">
        <p>План: <span class="font-mono">{{ $plan }}</span></p>
        <p>Клиенты: {{ $workspace->clients()->count() }} / {{ $limits['clients'] ?? '∞' }}</p>
        <p>Активы: {{ $workspace->assets()->count() }} / {{ $limits['assets'] ?? '∞' }}</p>
        @if ($subscription)
            <p>Статус: <span class="font-mono">{{ $subscription->status->value }}</span></p>
            @if ($subscription->trial_ends_at)
                <p>Триал до: <span class="font-mono">{{ $subscription->trial_ends_at->toDateString() }}</span></p>
            @endif
        @endif
        <p class="text-sm text-muted">
            White-label отчёты — на тарифе Agency.
        </p>
        <div class="flex flex-wrap gap-2 pt-2">
            <x-ui.button variant="accent" wire:click="checkout('starter')">Starter ${{ config('billing.plans.starter.price') }}</x-ui.button>
            <x-ui.button variant="accent" wire:click="checkout('agency')">Agency ${{ config('billing.plans.agency.price') }}</x-ui.button>
            <form method="POST" action="{{ route('billing.cancel') }}">
                @csrf
                <x-ui.button variant="danger" type="submit">Отменить</x-ui.button>
            </form>
        </div>
    </x-ui.card>

    <h2 class="mt-8 mb-3 text-sm text-muted">История платежей</h2>
    <x-ui.card class="divide-y divide-border overflow-hidden">
        @forelse ($events as $event)
            <p class="px-4 py-3 font-mono text-sm">
                {{ $event->created_at->toDateTimeString() }} · {{ $event->type }} · {{ $event->plan?->value }} · {{ $event->amount }}
            </p>
        @empty
            <p class="px-4 py-6 text-sm text-muted">Пока нет платежей.</p>
        @endforelse
    </x-ui.card>
</div>
