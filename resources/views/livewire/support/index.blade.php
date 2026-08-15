<div>
    <x-page-head :title="__('app.support.title')" :sub="__('app.support.sub')">
        <x-ui.button variant="accent" wire:click="$set('showCreate', true)">
            <i class="mdi mdi-plus me-1"></i>{{ __('app.support.new') }}
        </x-ui.button>
    </x-page-head>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0">{{ __('app.support.requests') }}</h5>
            <select class="form-select form-select-sm w-auto" wire:model.live="status">
                <option value="all">{{ __('app.support.all') }}</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.support.subject') }}</th>
                        <th>{{ __('app.support.replies') }}</th>
                        <th>{{ __('app.support.status') }}</th>
                        <th>{{ __('app.support.priority') }}</th>
                        <th>{{ __('app.support.updated') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('support.show', $ticket) }}" class="fw-semibold text-decoration-none" wire:navigate>
                                    {{ $ticket->subject }}
                                </a>
                            </td>
                            <td>
                                @if ($ticket->unread_admin_count > 0)
                                    <span class="ticket-badge ticket-badge-danger" title="{{ __('app.support.admin_replied') }}">
                                        <i class="mdi mdi-email"></i>{{ $ticket->unread_admin_count }}
                                    </span>
                                @elseif ($ticket->admin_replies_count > 0)
                                    <span class="ticket-badge ticket-badge-success" title="{{ __('app.support.replies') }}">
                                        <i class="mdi mdi-forum-outline"></i>{{ $ticket->admin_replies_count }}
                                    </span>
                                @else
                                    <span class="ticket-badge ticket-badge-warning" title="{{ __('app.support.no_reply') }}">
                                        <i class="mdi mdi-clock-outline"></i>—
                                    </span>
                                @endif
                            </td>
                            <td>
                                <x-support.status-badge :status="$ticket->status" />
                            </td>
                            <td>
                                <x-support.priority-badge :priority="$ticket->priority" />
                            </td>
                            <td class="text-muted">{{ $ticket->last_message_at?->diffForHumans() }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('support.show', $ticket) }}" class="btn btn-sm btn-light" wire:navigate>
                                    {{ __('app.support.open') }}
                                </a>
                                <button
                                    type="button"
                                    class="ticket-delete ms-1"
                                    title="{{ __('app.support.delete') }}"
                                    wire:click="deleteTicket({{ $ticket->id }})"
                                    wire:confirm="{{ __('app.support.delete_confirm') }}"
                                >
                                    <i class="mdi mdi-trash-can-outline"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">{{ __('app.support.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $tickets->links() }}</div>

    @if ($showCreate)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" wire:submit="create">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('app.support.new') }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('showCreate', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('app.support.subject') }}</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" wire:model="subject">
                            @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('app.support.priority') }}</label>
                            <select class="form-select @error('priority') is-invalid @enderror" wire:model="priority">
                                @foreach ($priorities as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="form-label">{{ __('app.support.message') }}</label>
                            <textarea class="form-control @error('body') is-invalid @enderror" rows="6" wire:model="body"></textarea>
                            @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <hr class="support-compose-divider">
                        <x-ui.file
                            groupClass="mb-0"
                            :value="$attachment"
                            :error="$errors->first('attachment')"
                            :title="__('app.support.attachment_hint')"
                            wire:model="attachment"
                        />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="$set('showCreate', false)">{{ __('app.common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('app.support.send') }}</span>
                            <span wire:loading>{{ __('app.support.uploading') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
