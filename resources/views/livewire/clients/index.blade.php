<div>
    @php($isOwner = auth()->user()->ownsCurrentWorkspace())
    <x-page-head :title="__('app.clients.title')" :sub="__('app.clients.sub')">
        @if ($isOwner)
            <x-ui.button href="{{ route('import') }}" wire:navigate>
                <i class="mdi mdi-upload-outline me-1"></i>{{ __('app.common.import') }}
            </x-ui.button>
        @endif
        <x-ui.button href="{{ route('export.clients') }}">
            <i class="mdi mdi-download me-1"></i>{{ __('app.common.csv') }}
        </x-ui.button>
        <x-ui.button variant="accent" wire:click="create">
            <i class="mdi mdi-plus me-1"></i>{{ __('app.common.add') }}
        </x-ui.button>
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-4">
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="{{ __('app.filters.search_clients') }}" />
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.table.client') }}</th>
                        <th>{{ __('app.table.contact') }}</th>
                        <th>{{ __('app.fields.email') }}</th>
                        <th>{{ __('app.table.website') }}</th>
                        <th>{{ __('app.table.assets') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('clients.show', $client) }}" class="text-decoration-none" wire:navigate>{{ $client->name }}</a>
                            </td>
                            <td class="text-muted">{{ $client->contact_name ?: __('app.common.empty') }}</td>
                            <td class="text-muted">{{ $client->email ?: __('app.clients.no_email') }}</td>
                            <td>
                                @if ($client->websiteHref())
                                    <a href="{{ $client->websiteHref() }}" class="text-decoration-none" target="_blank" rel="noopener">{{ $client->website }}</a>
                                @else
                                    <span class="text-muted">{{ __('app.common.empty') }}</span>
                                @endif
                            </td>
                            <td><span class="badge badge-soft-primary">{{ $client->assets_count }}</span></td>
                            <td class="text-end">
                                <div class="table-row-actions">
                                    @if ($isOwner)
                                        <x-ui.button variant="danger" icon="trash-can-outline" :tip="__('app.common.delete')" wire:click="delete({{ $client->id }})" wire:confirm="{{ __('app.clients.confirm_delete') }}" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="ny-list-empty">
                                {{ __('app.clients.empty') }}
                                <button type="button" class="btn btn-link p-0 align-baseline" wire:click="create">{{ __('app.common.add') }}</button>
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

    <x-ui.modal :open="$showForm" :title="$editingId ? __('app.clients.edit') : __('app.clients.new')">
        <form wire:submit="save">
            <x-ui.input :label="__('app.fields.name')" wire:model="name" />
            @error('name') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.contact')" wire:model="contactName" placeholder="{{ __('app.fields.contact_placeholder') }}" />
            <x-ui.input :label="__('app.fields.email')" type="email" wire:model="email" />
            @error('email') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.website')" wire:model="website" placeholder="example.com" />
            <x-ui.textarea :label="__('app.fields.notes')" wire:model="notes" rows="3" />
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button type="button" wire:click="close">{{ __('app.common.cancel') }}</x-ui.button>
                <x-ui.button variant="accent" type="submit">{{ __('app.common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
