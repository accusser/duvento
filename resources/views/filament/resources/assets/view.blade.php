<x-filament-panels::page>
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('admin.fields.name') }}</dt>
                <dd class="col-sm-9">{{ $record->name }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.workspace') }}</dt>
                <dd class="col-sm-9">{{ $record->workspace?->name ?? __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.client') }}</dt>
                <dd class="col-sm-9">
                    @if ($record->client)
                        <a href="{{ \App\Filament\Resources\Clients\ClientResource::getUrl('view', ['record' => $record->client]) }}" wire:navigate>
                            {{ $record->client->name }}
                        </a>
                    @else
                        {{ __('admin.placeholders.empty') }}
                    @endif
                </dd>

                <dt class="col-sm-3">{{ __('admin.fields.type') }}</dt>
                <dd class="col-sm-9">{{ $record->assetType?->displayLabel() ?? __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.expires_at') }}</dt>
                <dd class="col-sm-9">{{ $record->expires_at?->toDateString() ?? __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.status') }}</dt>
                <dd class="col-sm-9">
                    <span class="{{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span>
                </dd>

                <dt class="col-sm-3">{{ __('admin.fields.owner') }}</dt>
                <dd class="col-sm-9">{{ $record->owner->label() }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.payer') }}</dt>
                <dd class="col-sm-9">{{ $record->payer->label() }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.auto_renew') }}</dt>
                <dd class="col-sm-9">{{ $record->auto_renew->label() }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.notice_email') }}</dt>
                <dd class="col-sm-9">{{ $record->notice_email ?: __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.ssl_check') }}</dt>
                <dd class="col-sm-9">{{ $record->ssl_check_enabled ? __('admin.enums.auto_renew.yes') : __('admin.enums.auto_renew.no') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.last_checked_at') }}</dt>
                <dd class="col-sm-9">{{ $record->last_checked_at?->toDateTimeString() ?? __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.renewal_cost') }}</dt>
                <dd class="col-sm-9">
                    @if ($record->renewal_cost !== null)
                        {{ $record->renewal_cost }} {{ $record->currency }}
                    @else
                        {{ __('admin.placeholders.empty') }}
                    @endif
                </dd>

                <dt class="col-sm-3">{{ __('admin.fields.notes') }}</dt>
                <dd class="col-sm-9">{{ $record->notes ?: __('admin.placeholders.empty') }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('admin.fields.history') }}</h5>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($logs as $log)
                <div class="list-group-item">
                    <div class="fw-semibold">{{ \App\Support\ActivityAction::label((string) $log->action) }}</div>
                    <div class="small text-muted">
                        {{ $log->created_at?->toDateTimeString() }}
                        · {{ $log->actorName() ?? __('admin.placeholders.system') }}
                        @if ($rows = \App\Support\ActivityAction::propertyRows(is_array($log->properties) ? $log->properties : null))
                            · {{ collect($rows)->map(fn (array $row): string => $row['value'])->implode(' · ') }}
                        @endif
                    </div>
                </div>
            @empty
                <div class="list-group-item text-muted">{{ __('admin.placeholders.empty') }}</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
