<div>
    <x-page-head :title="__('app.reminders.title')" :sub="__('app.reminders.sub')">
        <x-ui.button type="button" wire:click="runNow">
            <i class="mdi mdi-send-outline me-1"></i>{{ __('app.reminders.run_now') }}
        </x-ui.button>
    </x-page-head>

    <div class="alert alert-info d-flex gap-2">
        <i class="mdi mdi-information-outline fs-5"></i>
        <div>
            {!! __('app.reminders.cron_hint', [
                'field' => '<code>expires_at</code>',
                'journal' => '<a href="'.route('activity').'" class="alert-link" wire:navigate>'.e(__('app.reminders.journal')).'</a>',
                'action' => e(__('app.activity.actions')['reminder.sent'] ?? 'reminder.sent'),
            ]) !!}
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>{{ __('app.reminders.rules') }}</h5></div>
        <div class="card-body">
            <form wire:submit="save">
                @foreach ($days as $index => $day)
                    <div class="mb-3">
                        <label class="form-label">{{ __('app.fields.days_before') }}</label>
                        <div class="d-flex align-items-stretch gap-2">
                            <x-ui.input class="flex-grow-1" type="number" min="1" wire:model="days.{{ $index }}" />
                            <x-ui.button class="ny-input-action" type="button" icon="minus" :tip="__('app.reminders.remove')" wire:click="removeDay({{ $index }})" />
                        </div>
                    </div>
                @endforeach
                @error('days') <div class="invalid-feedback d-block mb-3">{{ $message }}</div> @enderror

                <div class="d-flex gap-2">
                    <x-ui.button type="button" wire:click="addDay">{{ __('app.reminders.add_rule') }}</x-ui.button>
                    <x-ui.button variant="accent" type="submit">{{ __('app.common.save') }}</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3 {{ $workspace->telegramConnected() ? '' : 'border-warning' }}">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <div class="fw-semibold d-flex align-items-center gap-2">
                        <i class="mdi mdi-send-outline"></i>
                        {{ __('app.reminders.telegram_title') }}
                    </div>
                    <p class="small text-muted mb-0 mt-2">{{ __('app.reminders.telegram_sub') }}</p>
                </div>
                @if ($workspace->telegramConnected())
                    <span class="badge badge-soft-success">
                        <i class="mdi mdi-check-circle-outline me-1"></i>{{ __('app.reminders.telegram_on') }}
                    </span>
                @else
                    <span class="badge badge-soft-warning">
                        <i class="mdi mdi-alert-outline me-1"></i>{{ __('app.reminders.telegram_off') }}
                    </span>
                @endif
            </div>

            @if ($workspace->telegramConnected())
                <p class="mb-3">
                    {{ __('app.reminders.telegram_connected', [
                        'bot' => $workspace->telegram_bot_username ? '@'.$workspace->telegram_bot_username : '—',
                        'chat' => $workspace->telegram_chat_title ?: $workspace->telegram_chat_id,
                    ]) }}
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <x-ui.button variant="accent" type="button" wire:click="sendTelegramTest">{{ __('app.reminders.telegram_test') }}</x-ui.button>
                    <x-ui.button variant="danger" type="button" wire:click="disconnectTelegram" wire:confirm="{{ __('app.reminders.telegram_disconnect_confirm') }}">
                        {{ __('app.reminders.telegram_disconnect') }}
                    </x-ui.button>
                </div>
                @error('telegramToken') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
            @else
                <div class="alert alert-light border mb-3">
                    <div>
                        <div class="fw-semibold mb-2">{{ __('app.reminders.telegram_howto') }}</div>
                        <ol class="small mb-0 ps-3">
                            <li>{!! __('app.reminders.telegram_step1', ['bot' => '<a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a>']) !!}</li>
                            <li>{{ __('app.reminders.telegram_step2') }}</li>
                            <li>{{ __('app.reminders.telegram_step3') }}</li>
                            <li>{{ __('app.reminders.telegram_step4') }}</li>
                            <li>{!! __('app.reminders.telegram_step5', ['idbot' => '<a href="https://t.me/userinfobot" target="_blank" rel="noopener">@userinfobot</a>']) !!}</li>
                        </ol>
                    </div>
                </div>

                <x-ui.input :label="__('app.reminders.telegram_token')" type="password" autocomplete="off" wire:model="telegramToken" />
                @error('telegramToken') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <x-ui.button type="button" wire:click="findTelegramChats">{{ __('app.reminders.telegram_find') }}</x-ui.button>
                </div>

                @if ($telegramChats !== [])
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach ($telegramChats as $chat)
                            <x-ui.button type="button" wire:click="$set('telegramChatId', '{{ $chat['id'] }}')" class="{{ $telegramChatId === $chat['id'] ? 'active' : '' }}">
                                {{ $chat['title'] }}
                            </x-ui.button>
                        @endforeach
                    </div>
                @endif

                <x-ui.input :label="__('app.reminders.telegram_chat_manual')" wire:model="telegramChatId" />
                @error('telegramChatId') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror

                <x-ui.button variant="accent" type="button" wire:click="connectTelegram">{{ __('app.reminders.telegram_connect') }}</x-ui.button>
            @endif
        </div>
    </div>
</div>
