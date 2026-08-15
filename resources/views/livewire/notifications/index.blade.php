<div>
    <x-page-head :title="__('app.notifications.title')" :sub="__('app.notifications.sub')">
        @if ($notifications->total() > 0)
            <x-ui.button icon="email-check-outline" :tip="__('app.notifications.mark_all')" wire:click="markAll" />
            <x-ui.button variant="danger" icon="notification-clear-all" :tip="__('app.notifications.clear')" wire:click="clear" wire:confirm="{{ __('app.notifications.confirm_clear') }}" />
        @endif
    </x-page-head>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $item)
                <div class="list-group-item {{ $item['read'] ? '' : 'bg-body-secondary' }}">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <span class="notif-ic bg-primary-soft flex-shrink-0"><i class="mdi {{ $item['icon'] }}"></i></span>
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}" class="min-w-0 flex-grow-1 text-decoration-none" wire:navigate>
                                <p class="mb-1 fw-semibold text-body">{{ $item['title'] }}</p>
                                @if ($item['body']) <p class="mb-1 small text-muted text-truncate">{{ $item['body'] }}</p> @endif
                                <code class="small">{{ $item['created_at']->format('Y-m-d H:i') }}</code>
                            </a>
                        @else
                            <div class="min-w-0 flex-grow-1">
                                <p class="mb-1 fw-semibold">{{ $item['title'] }}</p>
                                @if ($item['body']) <p class="mb-1 small text-muted text-truncate">{{ $item['body'] }}</p> @endif
                                <code class="small">{{ $item['created_at']->format('Y-m-d H:i') }}</code>
                            </div>
                        @endif
                        <div class="table-row-actions flex-shrink-0">
                            @if (! $item['read'])
                                <x-ui.button icon="check" :tip="__('app.notifications.mark_read')" wire:click="markRead({{ $item['id'] }}, '{{ $item['type'] }}')" />
                            @endif
                            <x-ui.button variant="danger" icon="trash-can-outline" :tip="__('app.common.delete')" wire:click="delete({{ $item['id'] }}, '{{ $item['type'] }}')" />
                        </div>
                    </div>
                </div>
            @empty
                <div class="ny-list-empty">
                    {{ __('app.notifications.empty') }}
                    @if (auth()->user()->ownsCurrentWorkspace())
                        <a href="{{ route('settings.reminders') }}" wire:navigate>{{ __('app.notifications.setup') }}</a>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
