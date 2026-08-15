<x-filament-panels::page>
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('admin.fields.name') }}</dt>
                <dd class="col-sm-9">{{ $record->name }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.email') }}</dt>
                <dd class="col-sm-9">{{ $record->email }}</dd>

                <dt class="col-sm-3">{{ __('admin.fields.created_at') }}</dt>
                <dd class="col-sm-9">{{ $record->created_at?->toDateTimeString() }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('admin.fields.workspaces') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.fields.workspace') }}</th>
                        <th>{{ __('admin.fields.role') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($record->workspaces as $workspace)
                        <tr>
                            <td>
                                <a href="{{ \App\Filament\Resources\Workspaces\WorkspaceResource::getUrl('edit', ['record' => $workspace]) }}" wire:navigate>
                                    {{ $workspace->name }}
                                </a>
                            </td>
                            <td>{{ \App\Enums\WorkspaceRole::tryFrom((string) $workspace->pivot->role)?->label() ?? $workspace->pivot->role }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-muted">{{ __('admin.placeholders.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
