<x-filament-panels::page>
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('admin.fields.when') }}</dt>
                <dd class="col-sm-9">{{ $record->created_at?->toDateTimeString() }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.workspace') }}</dt>
                <dd class="col-sm-9">{{ $record->workspace?->name ?? __('admin.placeholders.empty') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.who') }}</dt>
                <dd class="col-sm-9">{{ $record->actorName() ?? __('admin.placeholders.system') }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.action') }}</dt>
                <dd class="col-sm-9">
                    {{ \App\Support\ActivityAction::label((string) $record->action) }}
                </dd>

                <dt class="col-sm-3">{{ __('admin.fields.properties') }}</dt>
                <dd class="col-sm-9">
                    @php($rows = \App\Support\ActivityAction::propertyRows(is_array($record->properties) ? $record->properties : null))
                    @if ($rows !== [])
                        <div class="activity-props">
                            @foreach ($rows as $row)
                                <div class="activity-prop">
                                    <div class="activity-prop-label">{{ $row['label'] }}</div>
                                    <div class="activity-prop-value">{{ $row['value'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{ __('admin.placeholders.empty') }}
                    @endif
                </dd>
            </dl>
        </div>
    </div>
</x-filament-panels::page>
