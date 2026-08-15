@php
    $isOwner = auth()->user()->ownsCurrentWorkspace();
    $icons = [
        'asset.renewed' => ['mdi-calendar-check', 'mint'],
        'reminder.sent' => ['mdi-email-fast-outline', 'cool'],
        'asset.created' => ['mdi-plus', ''],
        'asset.deleted' => ['mdi-delete-outline', 'warm'],
        'ssl.updated' => ['mdi-lock-check-outline', 'mint'],
    ];
@endphp

<div>
    <x-page-head :title="__('app.activity.title')" :sub="__('app.activity.sub')">
        <x-ui.button href="{{ route('export.activity') }}">
            <i class="mdi mdi-download me-1"></i>{{ __('app.common.csv') }}
        </x-ui.button>
        @if ($isOwner && $logs->total() > 0)
            <x-ui.button variant="danger" icon="notification-clear-all" :tip="__('app.activity.clear')" wire:click="clear" wire:confirm="{{ __('app.activity.confirm_clear') }}" />
        @endif
    </x-page-head>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($logs as $log)
                @php [$mdi, $tone] = $icons[$log->action] ?? ['mdi-history', 'mint']; @endphp
                <div class="list-group-item">
                    <div class="d-flex align-items-center gap-3">
                        <span class="stat-icon-wrap flex-shrink-0 {{ $tone }}" style="width:40px;height:40px;font-size:1.1rem;"><i class="mdi {{ $mdi }}"></i></span>
                        <div class="min-w-0">
                            <p class="mb-1 fw-semibold">{{ __('app.activity.actions')[$log->action] ?? $log->action }}</p>
                            <p class="mb-0 small text-muted">
                                <code>{{ $log->created_at->format('Y-m-d H:i') }}</code>
                                · {{ $log->user?->name ?? __('app.common.system') }}
                                @if (!empty($log->properties['name']))
                                    · {{ $log->properties['name'] }}
                                @endif
                                @if (!empty($log->properties['days_before']))
                                    · {{ __('app.notifications.days_before', ['days' => $log->properties['days_before']]) }}
                                @endif
                                @if (!empty($log->properties['email']))
                                    · {{ $log->properties['email'] }}
                                @endif
                                @if (!empty($log->properties['from']) || !empty($log->properties['to']))
                                    · {{ $log->properties['from'] ?? __('app.common.empty') }} → {{ $log->properties['to'] ?? __('app.common.empty') }}
                                @endif
                                @if (!empty($log->properties['days']) && is_array($log->properties['days']))
                                    · {{ __('app.activity.days', ['days' => implode(', ', $log->properties['days'])]) }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="ny-list-empty">
                    {{ __('app.activity.empty') }}
                    <a href="{{ route('assets') }}" wire:navigate>{{ __('app.activity.empty_add') }}</a>
                    {{ __('app.activity.empty_after') }}
                </div>
            @endforelse
        </div>
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
</div>
