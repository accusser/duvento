<div>
    <x-page-head :title="__('app.billing.title')" :sub="__('app.billing.sub')" />

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-2">{{ __('app.billing.plan') }} <code>{{ $plan }}</code></p>
            <p class="mb-2">{{ __('app.billing.clients') }} {{ $workspace->clients()->count() }} / {{ $limits['clients'] ?? '∞' }}</p>
            <p class="mb-2">{{ __('app.billing.assets') }} {{ $workspace->assets()->count() }} / {{ $limits['assets'] ?? '∞' }}</p>
            @if ($subscription)
                <p class="mb-2">{{ __('app.billing.status') }} <code>{{ $subscription->status->value }}</code></p>
                @if ($subscription->trial_ends_at)
                    <p class="mb-2">{{ __('app.billing.trial_until') }} <code>{{ $subscription->trial_ends_at->toDateString() }}</code></p>
                @endif
            @endif
            <p class="small text-muted mb-3">{{ __('app.billing.white_label') }}</p>
            <div class="d-flex flex-wrap gap-2">
                <x-ui.button variant="accent" wire:click="checkout('starter')">Starter ${{ config('billing.plans.starter.price') }}</x-ui.button>
                <x-ui.button variant="accent" wire:click="checkout('agency')">Agency ${{ config('billing.plans.agency.price') }}</x-ui.button>
                <form method="POST" action="{{ route('billing.cancel') }}">
                    @csrf
                    <x-ui.button variant="danger" type="submit">{{ __('app.billing.cancel') }}</x-ui.button>
                </form>
            </div>
        </div>
    </div>

    <h6 class="text-muted mb-2">{{ __('app.billing.payments') }}</h6>
    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($events as $event)
                <div class="list-group-item">
                    <code class="small">{{ $event->created_at->toDateTimeString() }} · {{ $event->type }} · {{ $event->plan?->value }} · {{ $event->amount }}</code>
                </div>
            @empty
                <div class="ny-list-empty">{{ __('app.billing.empty_payments') }}</div>
            @endforelse
        </div>
    </div>
</div>
