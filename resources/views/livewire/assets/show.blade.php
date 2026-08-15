@php
    $isOwner = auth()->user()->ownsCurrentWorkspace();
    $days = $asset->days_left;
    $expiryHint = match (true) {
        $days === null => __('app.assets.no_date'),
        $days < 0 => __('app.assets.days_overdue', ['count' => abs($days)]),
        $days === 0 => __('app.assets.days_today'),
        default => __('app.assets.days_left', ['count' => $days]),
    };
@endphp

<div>
    <a href="{{ route('assets') }}" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1 mb-3" wire:navigate>
        <i class="mdi mdi-arrow-left"></i> {{ __('app.assets.back') }}
    </a>

    <x-page-head :title="$asset->name" :sub="$asset->assetType?->displayLabel()">
        <span class="{{ $asset->status->badgeClass() }}">
            <span class="{{ $asset->status->dotClass() }} me-1"></span>
            {{ $asset->status->label() }}
        </span>
        <x-ui.button variant="soft" icon="calendar-refresh" :tip="__('app.assets.renew')" wire:click="beginRenew" />
        <x-ui.button variant="accent" icon="pencil-outline" :tip="__('app.common.edit')" href="{{ route('assets.edit', $asset) }}" wire:navigate />
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label">{{ __('app.fields.expires') }}</div>
                    <div class="fw-semibold">{{ $asset->expires_at?->toDateString() ?? __('app.common.empty') }}</div>
                    <div class="small text-muted">{{ $expiryHint }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label">{{ __('app.fields.owner') }}</div>
                    <div class="fw-semibold">{{ $asset->owner->label() }}</div>
                    <div class="small text-muted">{{ __('app.fields.auto_renew') }}: {{ $asset->auto_renew->label() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label">{{ __('app.fields.payer') }}</div>
                    <div class="fw-semibold">{{ $asset->payer->label() }}</div>
                    <div class="small text-muted">{{ $asset->notice_email ?: __('app.clients.no_email') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label">{{ __('app.fields.client') }}</div>
                    <div class="fw-semibold">
                        @if ($asset->client)
                            <a href="{{ route('clients.show', $asset->client) }}" class="text-decoration-none" wire:navigate>{{ $asset->client->name }}</a>
                        @else
                            {{ __('app.common.empty') }}
                        @endif
                    </div>
                    <div class="small text-muted">{{ __('app.clients.updated', ['date' => $asset->updated_at->translatedFormat('d M Y')]) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('app.assets.details') }}</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-muted mb-1">{{ __('app.fields.notice_email') }}</div>
                    <div>{{ $asset->notice_email ?: __('app.clients.not_set') }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted mb-1">{{ __('app.assets.reminders') }}</div>
                    <div>{{ $reminderDays->implode(', ') ?: '—' }} {{ __('app.assets.days_short') }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted mb-1">{{ __('app.fields.renewal_cost') }}</div>
                    <div>
                        {{ $asset->renewal_cost === null
                            ? __('app.clients.not_set')
                            : \App\Support\UpcomingPayments::format($asset->currency ?: $asset->workspace->currencyCode(), $asset->renewal_cost) }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted mb-1">{{ __('app.fields.ssl_check') }}</div>
                    <div>{{ $asset->ssl_check_enabled ? __('app.assets.ssl_on') : __('app.assets.ssl_off') }}</div>
                </div>
                @if ($asset->ssl_check_enabled)
                    <div class="col-md-6">
                        <div class="small text-muted mb-1">{{ __('app.assets.last_checked') }}</div>
                        <div>{{ $asset->last_checked_at?->format('Y-m-d H:i') ?? __('app.assets.never_checked') }}</div>
                    </div>
                @endif
                <div class="col-12">
                    <div class="small text-muted mb-1">{{ __('app.fields.notes') }}</div>
                    <div>{{ $asset->notes ?: __('app.clients.no_notes') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('app.assets.activity') }}</h5></div>
        <div class="list-group list-group-flush">
            @forelse ($logs as $log)
                <div class="list-group-item">
                    <div class="fw-semibold">{{ __('app.activity.actions.'.$log->action) }}</div>
                    <div class="small text-muted">
                        <code>{{ $log->created_at->format('Y-m-d H:i') }}</code>
                        · {{ $log->user?->name ?? __('app.common.system') }}
                        @if (! empty($log->properties['from']) || ! empty($log->properties['to']))
                            · {{ $log->properties['from'] ?? '—' }} → {{ $log->properties['to'] ?? '—' }}
                        @endif
                        @if (! empty($log->properties['email']))
                            · {{ $log->properties['email'] }}
                        @endif
                        @if (! empty($log->properties['days_before']))
                            · {{ __('app.notifications.days_before', ['days' => $log->properties['days_before']]) }}
                        @endif
                    </div>
                </div>
            @empty
                <div class="ny-list-empty">{{ __('app.assets.activity_empty') }}</div>
            @endforelse
        </div>
    </div>

    @if ($isOwner)
        <div class="card border-danger-subtle">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="fw-semibold text-danger">{{ __('app.assets.delete_title') }}</div>
                    <div class="small text-muted">{{ __('app.assets.delete_hint') }}</div>
                </div>
                <x-ui.button variant="danger" wire:click="delete" wire:confirm="{{ __('app.assets.confirm_delete') }}">{{ __('app.assets.delete_action') }}</x-ui.button>
            </div>
        </div>
    @endif

    <x-ui.modal :open="$showRenew" :title="__('app.assets.renew_title')">
        <form wire:submit="confirmRenew">
            <p class="mb-3">{{ __('app.assets.renew_short', ['name' => $asset->name]) }}</p>
            <x-ui.input :label="__('app.fields.expires')" type="date" wire:model="renewDate" />
            @error('renewDate') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button type="button" wire:click="close">{{ __('app.common.cancel') }}</x-ui.button>
                <x-ui.button variant="accent" type="submit">{{ __('app.assets.renew') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
