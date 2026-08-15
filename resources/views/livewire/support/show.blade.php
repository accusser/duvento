<div>
    <a href="{{ route('support') }}" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1 mb-3" wire:navigate>
        <i class="mdi mdi-arrow-left"></i> {{ __('app.support.back') }}
    </a>

    <x-page-head :title="$ticket->subject" :sub="$ticket->workspace->name">
        <x-support.status-badge :status="$ticket->status" />
        <x-support.priority-badge :priority="$ticket->priority" />
        @if ($ticket->status->value !== 'closed')
            <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                wire:click="closeTicket"
                wire:confirm="{{ __('app.support.close_confirm') }}"
            >
                <i class="mdi mdi-check-circle-outline me-1"></i>{{ __('app.support.close_ticket') }}
            </button>
        @endif
        <button
            type="button"
            class="ticket-delete"
            title="{{ __('app.support.delete') }}"
            wire:click="deleteTicket"
            wire:confirm="{{ __('app.support.delete_confirm') }}"
        >
            <i class="mdi mdi-trash-can-outline"></i>
        </button>
    </x-page-head>

    <div class="card support-chat">
        <div class="card-body support-chat-messages">
            @foreach ($ticket->messages as $message)
                @php($isClient = $message->authorType() === \App\Enums\TicketAuthorType::Client)
                <div class="support-message-row {{ $isClient ? 'is-client' : 'is-admin' }}">
                    <div class="support-message">
                        <div class="support-message-author">
                            {{ $message->authorName() }}
                            <span>{{ $message->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="support-message-body">{!! nl2br(e($message->body)) !!}</div>
                        @foreach ($message->attachments as $attachment)
                            <x-support.attachment :attachment="$attachment" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <form class="card-footer" wire:submit="reply">
            <label class="form-label fw-semibold">{{ __('app.support.reply') }}</label>
            <textarea class="form-control @error('body') is-invalid @enderror" rows="4" wire:model="body" placeholder="{{ __('app.support.reply_placeholder') }}"></textarea>
            @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <hr class="support-compose-divider">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <x-ui.file
                    groupClass="mb-0 flex-grow-1"
                    :value="$attachment"
                    :error="$errors->first('attachment')"
                    :title="__('app.support.attachment_hint')"
                    wire:model="attachment"
                />
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="mdi mdi-send me-1"></i>{{ __('app.support.send') }}</span>
                    <span wire:loading>{{ __('app.support.uploading') }}</span>
                </button>
            </div>
            @if ($ticket->status->value === 'closed')
                <div class="small text-muted mt-2">{{ __('app.support.reopen_hint') }}</div>
            @endif
        </form>
    </div>
</div>
