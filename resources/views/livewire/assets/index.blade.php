<div>
    @php($isOwner = auth()->user()->ownsCurrentWorkspace())
    <x-page-head :title="__('app.assets.title')" :sub="__('app.assets.sub')">
        @if ($isOwner)
            <x-ui.button href="{{ route('import') }}" wire:navigate>
                <i class="mdi mdi-upload-outline me-1"></i>{{ __('app.common.import') }}
            </x-ui.button>
        @endif
        <x-ui.button href="{{ route('export.assets', request()->query()) }}">
            <i class="mdi mdi-download me-1"></i>{{ __('app.common.csv') }}
        </x-ui.button>
        <x-ui.button variant="accent" href="{{ route('assets.create', array_filter(['client_id' => $clientId ?: null])) }}" wire:navigate>
            <i class="mdi mdi-plus me-1"></i>{{ __('app.common.add') }}
        </x-ui.button>
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-lg">
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="{{ __('app.filters.search') }}" />
        </div>
        <div class="col-md-4 col-lg-2">
            <x-ui.select wire:model.live="status">
                <option value="">{{ __('app.filters.all_statuses') }}</option>
                @foreach (__('app.enums.status') as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="col-md-4 col-lg-2">
            <x-ui.select wire:model.live="clientId">
                <option value="">{{ __('app.filters.all_clients') }}</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="col-md-4 col-lg-2">
            <x-ui.select wire:model.live="typeId">
                <option value="">{{ __('app.filters.all_types') }}</option>
                @foreach ($systemTypes->concat($customTypes) as $type)
                    <option value="{{ $type->id }}">{{ $type->displayLabel() }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="col-md-4 col-lg-2">
            <x-ui.select wire:model.live="ownerFilter">
                <option value="">{{ __('app.filters.owner') }}</option>
                @foreach (__('app.enums.party') as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="col-md-4 col-lg-2">
            <x-ui.select wire:model.live="payerFilter">
                <option value="">{{ __('app.filters.payer') }}</option>
                @foreach (__('app.enums.party') as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="col-md-4 col-lg-2">
            <x-ui.select wire:model.live="expiry">
                <option value="">{{ __('app.filters.date') }}</option>
                <option value="missing">{{ __('app.filters.missing_date') }}</option>
                <option value="dated">{{ __('app.filters.with_date') }}</option>
            </x-ui.select>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.table.days') }}</th>
                        <th>{{ __('app.table.asset') }}</th>
                        <th>{{ __('app.table.client') }}</th>
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
                            <td>
                                @if ($asset->client)
                                    <a href="{{ route('clients.show', $asset->client) }}" class="text-muted text-decoration-none" wire:navigate>{{ $asset->client->name }}</a>
                                @endif
                            </td>
                            <td class="text-muted">
                                {{ $asset->assetType?->displayLabel() }}
                                @if ($asset->assetType && ! $asset->assetType->isSystem())
                                    <span class="badge badge-soft-primary">{{ __('app.common.custom') }}</span>
                                @endif
                            </td>
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
                                        <x-ui.button variant="danger" icon="trash-can-outline" :tip="__('app.common.delete')" wire:click="delete({{ $asset->id }})" wire:confirm="{{ __('app.assets.confirm_delete') }}" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="ny-list-empty">
                                {{ __('app.assets.empty') }}
                                <a href="{{ route('assets.create') }}" class="btn btn-link p-0 align-baseline" wire:navigate>{{ __('app.common.add') }}</a>
                                @if ($isOwner)
                                    {{ __('app.common.or') }}
                                    <a href="{{ route('import') }}" wire:navigate>{{ __('app.common.import_csv') }}</a>.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-ui.modal :open="$showRenew" :title="__('app.assets.renew_title')">
        <form wire:submit="confirmRenew">
            <p class="mb-3">{{ __('app.assets.renew_hint', ['name' => $renewingName]) }}</p>
            <x-ui.input :label="__('app.fields.expires')" type="date" wire:model="renewDate" />
            @error('renewDate') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button type="button" wire:click="close">{{ __('app.common.cancel') }}</x-ui.button>
                <x-ui.button variant="accent" type="submit">{{ __('app.assets.renew') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
