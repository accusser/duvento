<div>
    @php($isOwner = auth()->user()->ownsCurrentWorkspace())

    <a href="{{ route('clients') }}" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1 mb-3" wire:navigate>
        <i class="mdi mdi-arrow-left"></i> {{ __('app.clients.back') }}
    </a>

    <x-page-head :title="$client->name" :sub="$client->contact_name ?: __('app.clients.fallback_sub')">
        <x-ui.button href="{{ route('reports.show', $client) }}" wire:navigate>{{ __('app.clients.report') }}</x-ui.button>
        @if ($isOwner)
            @if ($client->public_token)
                <x-ui.button
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText(@js($client->publicUrl())).then(() => { copied = true; clearTimeout($el._t); $el._t = setTimeout(() => copied = false, 2000) })"
                    x-text="copied ? @js(__('app.clients.share_copied')) : @js(__('app.clients.share_copy'))"
                >{{ __('app.clients.share_copy') }}</x-ui.button>
            @else
                <x-ui.button type="button" wire:click="createPublicLink">{{ __('app.clients.share_create') }}</x-ui.button>
            @endif
        @endif
        <x-ui.button href="{{ route('assets.create', ['client_id' => $client->id]) }}" wire:navigate>{{ __('app.dashboard.add_asset') }}</x-ui.button>
        <x-ui.button variant="accent" wire:click="editClient">{{ __('app.common.edit') }}</x-ui.button>
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label">{{ __('app.fields.contact') }}</div>
                    <div class="fw-semibold">{{ $client->contact_name ?: __('app.clients.not_set') }}</div>
                    <div class="small text-muted">{{ $client->email ?: __('app.clients.no_email') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label">{{ __('app.clients.main_risk') }}</div>
                    <div class="fw-semibold">{{ $worstStatus->label() }}</div>
                    <div class="small text-muted">{{ __('app.clients.critical_count', ['count' => $highRisk]) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label">{{ __('app.clients.renewals') }}</div>
                    <div class="fw-semibold">{{ __('app.clients.upcoming_count', ['count' => $upcoming]) }}</div>
                    <div class="small text-muted">{{ __('app.clients.unknown_meta', ['payer' => $unknownPayer, 'owner' => $unknownOwner]) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-label">{{ __('app.clients.created') }}</div>
                    <div class="fw-semibold">{{ $client->created_at->translatedFormat('d M Y') }}</div>
                    <div class="small text-muted">{{ __('app.clients.updated', ['date' => $client->updated_at->translatedFormat('d M Y')]) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <x-cashflow-card :summary="$cashflow" :days="$cashflowDays" />
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('app.clients.about') }}</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-muted mb-1">{{ __('app.fields.website') }}</div>
                    @if ($client->websiteHref())
                        <a href="{{ $client->websiteHref() }}" target="_blank" rel="noopener">{{ $client->website }}</a>
                    @else
                        <div>{{ __('app.clients.not_set') }}</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="small text-muted mb-1">{{ __('app.fields.notes') }}</div>
                    <div>{{ $client->notes ?: __('app.clients.no_notes') }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($isOwner)
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold d-flex align-items-center gap-2">
                            <i class="mdi mdi-link-variant"></i>
                            {{ __('app.clients.share_title') }}
                        </div>
                        <p class="small text-muted mb-0 mt-2">{{ __('app.clients.share_hint') }}</p>
                    </div>
                    @if ($client->public_token)
                        <span class="badge badge-soft-success">{{ __('app.clients.share_on') }}</span>
                    @else
                        <span class="badge badge-soft-secondary">{{ __('app.clients.share_off') }}</span>
                    @endif
                </div>

                @if ($client->public_token)
                    <div class="input-group mt-3">
                        <input class="form-control" type="text" readonly value="{{ $client->publicUrl() }}" aria-label="{{ __('app.clients.share_copy') }}">
                        <x-ui.button
                            type="button"
                            x-data="{ copied: false }"
                            x-on:click="navigator.clipboard.writeText(@js($client->publicUrl())).then(() => { copied = true; clearTimeout($el._t); $el._t = setTimeout(() => copied = false, 2000) })"
                            x-text="copied ? @js(__('app.clients.share_copied')) : @js(__('app.clients.share_copy'))"
                        >{{ __('app.clients.share_copy') }}</x-ui.button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <x-ui.button type="button" wire:click="regeneratePublicLink" wire:confirm="{{ __('app.clients.share_regen_confirm') }}">{{ __('app.clients.share_regen') }}</x-ui.button>
                        <x-ui.button variant="danger" type="button" wire:click="disablePublicLink" wire:confirm="{{ __('app.clients.share_disable_confirm') }}">{{ __('app.clients.share_disable') }}</x-ui.button>
                    </div>
                @else
                    <div class="mt-3">
                        <x-ui.button variant="accent" type="button" wire:click="createPublicLink">{{ __('app.clients.share_create') }}</x-ui.button>
                    </div>
                @endif
                @error('share') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0">{{ __('app.assets.title') }}</h5>
                <div class="small text-muted">{{ __('app.clients.assets_sub') }}</div>
            </div>
            <x-ui.button variant="accent" href="{{ route('assets.create', ['client_id' => $client->id]) }}" wire:navigate>{{ __('app.dashboard.add_asset') }}</x-ui.button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.table.days') }}</th>
                        <th>{{ __('app.table.asset') }}</th>
                        <th>{{ __('app.table.type') }}</th>
                        <th>{{ __('app.table.renews') }}</th>
                        <th>{{ __('app.table.pays') }}</th>
                        <th>{{ __('app.table.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr class="{{ $asset->status->borderClass() }}">
                            <td><span class="countdown-days">{{ $asset->days_left === null ? __('app.common.empty') : $asset->days_left }}</span></td>
                            <td>
                                <a href="{{ route('assets.show', $asset) }}" class="fw-semibold text-decoration-none" wire:navigate>{{ $asset->name }}</a>
                                <div class="small text-muted">{{ $asset->expires_at?->toDateString() ?? __('app.assets.no_date') }}</div>
                            </td>
                            <td class="text-muted">{{ $asset->assetType?->displayLabel() }}</td>
                            <td class="text-muted">{{ $asset->owner->label() }}</td>
                            <td class="text-muted">{{ $asset->payer->label() }}</td>
                            <td>
                                <span class="{{ $asset->status->badgeClass() }}">
                                    <span class="{{ $asset->status->dotClass() }} me-1"></span>
                                    {{ $asset->status->label() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="table-row-actions">
                                    <x-ui.button variant="soft" icon="calendar-refresh" :tip="__('app.assets.renew')" wire:click="beginRenew({{ $asset->id }})" />
                                    <x-ui.button icon="pencil-outline" :tip="__('app.common.edit')" href="{{ route('assets.edit', $asset) }}" wire:navigate />
                                    @if ($isOwner)
                                        <x-ui.button variant="danger" icon="trash-can-outline" :tip="__('app.common.delete')" wire:click="deleteAsset({{ $asset->id }})" wire:confirm="{{ __('app.assets.confirm_delete') }}" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="ny-list-empty">
                                {{ __('app.assets.empty') }}
                                <a href="{{ route('assets.create', ['client_id' => $client->id]) }}" class="btn btn-link p-0 align-baseline" wire:navigate>{{ __('app.common.add') }}</a>
                                {{ __('app.clients.empty_hint') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($isOwner)
        <div class="card border-danger-subtle">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="fw-semibold text-danger">{{ __('app.clients.delete_title') }}</div>
                    <div class="small text-muted">{{ __('app.clients.delete_hint') }}</div>
                </div>
                <x-ui.button variant="danger" wire:click="deleteClient" wire:confirm="{{ __('app.clients.confirm_delete') }}">{{ __('app.clients.delete_action') }}</x-ui.button>
            </div>
        </div>
    @endif

    <x-ui.modal :open="$showClientForm" :title="__('app.clients.edit')">
        <form wire:submit="saveClient">
            <x-ui.input :label="__('app.fields.name')" wire:model="name" />
            @error('name') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.contact')" wire:model="contactName" placeholder="{{ __('app.fields.contact_placeholder') }}" />
            <x-ui.input :label="__('app.fields.email')" type="email" wire:model="email" />
            @error('email') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.website')" wire:model="website" placeholder="example.com" />
            <x-ui.textarea :label="__('app.fields.notes')" wire:model="notes" rows="3" />
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button type="button" wire:click="closeForms">{{ __('app.common.cancel') }}</x-ui.button>
                <x-ui.button variant="accent" type="submit">{{ __('app.common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal :open="$showRenew" :title="__('app.assets.renew_title')">
        <form wire:submit="confirmRenew">
            <p class="mb-3">{{ __('app.assets.renew_short', ['name' => $renewingName]) }}</p>
            <x-ui.input :label="__('app.fields.expires')" type="date" wire:model="renewDate" />
            @error('renewDate') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button type="button" wire:click="closeForms">{{ __('app.common.cancel') }}</x-ui.button>
                <x-ui.button variant="accent" type="submit">{{ __('app.assets.renew') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
