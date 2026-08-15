<x-filament-panels::page>
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('admin.fields.name') }}</dt>
                <dd class="col-sm-9">{{ $record->name }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.workspace') }}</dt>
                <dd class="col-sm-9">{{ $record->workspace?->name ?? __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.contact_name') }}</dt>
                <dd class="col-sm-9">{{ $record->contact_name ?: __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.email') }}</dt>
                <dd class="col-sm-9">{{ $record->email ?: __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.website') }}</dt>
                <dd class="col-sm-9">{{ $record->website ?: __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.notes') }}</dt>
                <dd class="col-sm-9">{{ $record->notes ?: __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.created_at') }}</dt>
                <dd class="col-sm-9">{{ $record->created_at?->toDateTimeString() }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('admin.fields.assets') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.fields.name') }}</th>
                        <th>{{ __('admin.fields.type') }}</th>
                        <th>{{ __('admin.fields.expires_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($record->assets as $asset)
                        <tr>
                            <td>
                                <a href="{{ \App\Filament\Resources\Assets\AssetResource::getUrl('view', ['record' => $asset]) }}" wire:navigate>
                                    {{ $asset->name }}
                                </a>
                            </td>
                            <td>{{ $asset->assetType?->displayLabel() ?? __('admin.placeholders.empty') }}</td>
                            <td>{{ $asset->expires_at?->toDateString() ?? __('admin.placeholders.empty') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted">{{ __('admin.placeholders.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
