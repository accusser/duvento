<x-filament-widgets::widget class="fi-wi-ny-users">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('admin.widgets.latest_users') }}</h5>
            <a href="{{ \App\Filament\Resources\Users\UserResource::getUrl() }}" class="small" wire:navigate>{{ __('admin.widgets.all_users') }}</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.fields.name') }}</th>
                        <th>{{ __('admin.fields.email') }}</th>
                        <th>{{ __('admin.fields.workspaces') }}</th>
                        <th>{{ __('admin.fields.created_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php($url = $viewUrl($user))
                        <tr class="widget-row-link">
                            <td><a href="{{ $url }}" wire:navigate>{{ $user->name }}</a></td>
                            <td><a href="{{ $url }}" wire:navigate>{{ $user->email }}</a></td>
                            <td><a href="{{ $url }}" wire:navigate>{{ $user->workspaces->pluck('name')->join(', ') ?: __('admin.placeholders.empty') }}</a></td>
                            <td><a href="{{ $url }}" wire:navigate>{{ $user->created_at?->diffForHumans() }}</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted">{{ __('admin.placeholders.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>
