<x-filament-panels::page>
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card support-chat">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1">{{ $record->subject }}</h5>
                        <div class="small text-muted">
                            {{ $record->user->name }} · {{ $record->user->email }}
                        </div>
                    </div>
                    <a href="{{ \App\Filament\Resources\Workspaces\WorkspaceResource::getUrl('edit', ['record' => $record->workspace]) }}" class="btn btn-sm btn-light" wire:navigate>
                        <i class="mdi mdi-office-building-outline me-1"></i>{{ $record->workspace->name }}
                    </a>
                </div>

                <div class="card-body support-chat-messages">
                    @foreach ($record->messages()->with(['author', 'attachments'])->oldest()->get() as $message)
                        @php($isAdmin = $message->authorType() === \App\Enums\TicketAuthorType::Admin)
                        <div class="support-message-row {{ $isAdmin ? 'is-admin' : 'is-client' }}">
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
                    <label class="form-label fw-semibold">{{ __('admin.tickets.reply') }}</label>
                    <textarea class="form-control @error('body') is-invalid @enderror" rows="4" wire:model="body" placeholder="{{ __('admin.tickets.reply_placeholder') }}"></textarea>
                    @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <hr class="support-compose-divider">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <x-ui.file
                            groupClass="mb-0 flex-grow-1"
                            :value="$attachment"
                            :error="$errors->first('attachment')"
                            :title="__('admin.tickets.attachment_hint')"
                            wire:model="attachment"
                        />
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="mdi mdi-send me-1"></i>{{ __('admin.tickets.send') }}</span>
                            <span wire:loading>{{ __('admin.tickets.uploading') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-4">
            <form class="card" wire:submit="updateMeta">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.tickets.management') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('admin.fields.status') }}</label>
                        <select class="form-select" wire:model="status">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.tickets.priority') }}</label>
                        <select class="form-select" wire:model="priority">
                            @foreach ($priorityOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">{{ __('admin.tickets.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
