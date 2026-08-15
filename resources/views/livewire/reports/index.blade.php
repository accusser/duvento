<div>
    <x-page-head :title="__('app.reports.title')" :sub="__('app.reports.sub')">
        @if ($whiteLabel)
            <x-ui.button href="{{ route('reports.clients') }}">{{ __('app.reports.pdf') }}</x-ui.button>
        @endif
    </x-page-head>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="mdi mdi-download-outline"></i>
                {{ __('app.reports.export_title') }}
            </h5>
        </div>
        <div class="card-body ny-btn-stack">
            <x-ui.button href="{{ route('export.clients') }}">{{ __('app.reports.export_clients') }}</x-ui.button>
            <x-ui.button href="{{ route('export.assets') }}">{{ __('app.reports.export_assets') }}</x-ui.button>
            <x-ui.button href="{{ route('export.activity') }}">{{ __('app.reports.export_activity') }}</x-ui.button>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.table.client') }}</th>
                        <th>{{ __('app.table.contact') }}</th>
                        <th>{{ __('app.reports.stat_total') }}</th>
                        <th>{{ __('app.reports.stat_critical') }}</th>
                        <th>{{ __('app.reports.stat_unknown_date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        @php($client = $group['client'])
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('reports.show', $client) }}" class="text-decoration-none" wire:navigate>{{ $client->name }}</a>
                            </td>
                            <td class="text-muted">
                                {{ collect([$client->contact_name, $client->email])->filter()->implode(' · ') ?: __('app.reports.no_contact') }}
                            </td>
                            <td>{{ $group['counts']['total'] }}</td>
                            <td class="{{ $group['counts']['critical'] + $group['counts']['expired'] > 0 ? 'fw-semibold' : 'text-muted' }}">
                                {{ $group['counts']['expired'] + $group['counts']['critical'] }}
                            </td>
                            <td class="{{ $group['counts']['unknown_date'] > 0 ? 'fw-semibold' : 'text-muted' }}">
                                {{ $group['counts']['unknown_date'] }}
                            </td>
                            <td class="text-end">
                                <div class="table-row-actions">
                                    <x-ui.button icon="open-in-new" :tip="__('app.reports.open')" href="{{ route('reports.show', $client) }}" wire:navigate />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="ny-list-empty">
                                {{ __('app.reports.no_clients') }}
                                <a href="{{ route('clients') }}" wire:navigate>{{ __('app.reports.add_client') }}</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
