<x-filament-panels::page>
    @if ($notifications->isNotEmpty())
        <div class="d-flex justify-content-end gap-2 mb-3">
            <button type="button" class="btn btn-light" wire:click="markAll">
                <i class="mdi mdi-email-check-outline me-1"></i>{{ __('admin.notifications.mark_all') }}
            </button>
            <button
                type="button"
                class="btn btn-outline-danger"
                wire:click="clear"
                wire:confirm="{{ __('admin.notifications.confirm_clear') }}"
            >
                <i class="mdi mdi-notification-clear-all me-1"></i>{{ __('admin.notifications.clear') }}
            </button>
        </div>
    @endif

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $item)
                <div class="list-group-item {{ $item['read'] ? '' : 'bg-body-secondary' }}">
                    <div class="d-flex align-items-center gap-3">
                        <span class="notif-ic bg-primary-soft flex-shrink-0">
                            <i class="mdi {{ $item['icon'] }}"></i>
                        </span>
                        <a href="{{ $item['url'] }}" class="min-w-0 flex-grow-1 text-decoration-none">
                            <div class="fw-semibold text-body">{{ $item['title'] }}</div>
                            @if ($item['body'])
                                <div class="small text-muted text-truncate">{{ $item['body'] }}</div>
                            @endif
                            <div class="small text-muted">
                                {{ $item['meta'] }}@if ($item['meta']) · @endif{{ $item['created_at']->format('d.m.Y H:i') }}
                            </div>
                        </a>
                        <div class="table-row-actions flex-shrink-0">
                            @if (! $item['read'])
                                <button
                                    type="button"
                                    class="btn btn-sm btn-light btn-icon"
                                    wire:click="markRead('{{ $item['type'] }}', {{ $item['id'] }})"
                                    title="{{ __('admin.notifications.mark_read') }}"
                                >
                                    <i class="mdi mdi-check"></i>
                                </button>
                            @endif
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-icon"
                                wire:click="delete('{{ $item['type'] }}', {{ $item['id'] }})"
                                title="{{ __('admin.notifications.delete') }}"
                            >
                                <i class="mdi mdi-trash-can-outline"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="ny-list-empty">{{ __('admin.notifications.empty') }}</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
