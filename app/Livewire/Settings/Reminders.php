<?php

namespace App\Livewire\Settings;

use App\Enums\ReminderChannel;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\ReminderRule;
use App\Support\ActivityLogger;
use App\Support\RateLimits;
use App\Support\ReminderDispatcher;
use App\Support\TelegramNotifier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Reminders extends Component
{
    use InteractsWithWorkspace;

    public array $days = [];

    public string $telegramToken = '';

    public string $telegramChatId = '';

    public array $telegramChats = [];

    public function mount(): void
    {
        $this->assertOwner();
        $this->days = $this->workspace()->reminderRules()
            ->whereNull('asset_id')
            ->where('channel', ReminderChannel::Email)
            ->orderByDesc('days_before')
            ->pluck('days_before')
            ->all();

        if ($this->days === []) {
            $this->days = [30, 14, 7, 1];
        }
    }

    public function addDay(): void
    {
        $this->assertOwner();
        $this->days[] = 3;
    }

    public function removeDay(int $index): void
    {
        $this->assertOwner();
        unset($this->days[$index]);
        $this->days = array_values($this->days);
    }

    public function save(): void
    {
        $this->assertOwner();
        $days = collect($this->days)
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $this->validate([
            'days' => ['required', 'array', 'min:1'],
        ]);

        $workspace = $this->workspace();
        $workspace->reminderRules()->whereNull('asset_id')->delete();

        $days->each(fn (int $day) => ReminderRule::query()->create([
            'workspace_id' => $workspace->id,
            'asset_id' => null,
            'days_before' => $day,
            'channel' => ReminderChannel::Email,
        ]));

        $this->days = $days->all();
        ActivityLogger::log($workspace, 'reminders.updated', null, ['days' => $this->days]);
        $this->toast(__('app.flash.reminders_saved'));
    }

    public function findTelegramChats(TelegramNotifier $telegram): void
    {
        $this->assertOwner();
        $this->validateTelegramToken();
        RateLimits::hitOrFail('telegram:'.$this->workspace()->id, 8, 60, 'telegramToken');

        try {
            $this->telegramChats = $telegram->recentChats($this->telegramToken);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('telegramToken', __('app.reminders.telegram_failed'));

            return;
        }

        if ($this->telegramChats === []) {
            $this->addError('telegramChatId', __('app.reminders.telegram_no_chat'));

            return;
        }

        if (count($this->telegramChats) === 1) {
            $this->telegramChatId = $this->telegramChats[0]['id'];
        }
    }

    public function connectTelegram(TelegramNotifier $telegram): void
    {
        $this->assertOwner();
        $this->validate([
            ...$this->telegramTokenRules(),
            'telegramChatId' => ['required', 'string', 'max:64', 'regex:/^(-?\d+|@[A-Za-z0-9_]{5,})$/'],
        ]);
        RateLimits::hitOrFail('telegram:'.$this->workspace()->id, 8, 60, 'telegramToken');

        try {
            $bot = $telegram->bot($this->telegramToken);
            $chat = $telegram->chat($this->telegramToken, $this->telegramChatId);
            $telegram->send($this->telegramToken, $this->telegramChatId, __('app.reminders.telegram_test_body'));
        } catch (\Throwable $e) {
            report($e);
            $this->addError('telegramToken', __('app.reminders.telegram_failed'));

            return;
        }

        $this->workspace()->forceFill([
            'telegram_bot_token' => $this->telegramToken,
            'telegram_bot_username' => $bot['username'] ?? null,
            'telegram_chat_id' => $this->telegramChatId,
            'telegram_chat_title' => $telegram->chatTitle($chat) ?: $this->telegramChatId,
            'telegram_connected_at' => now(),
        ])->save();

        $this->reset(['telegramToken', 'telegramChatId', 'telegramChats']);
        $this->toast(__('app.flash.telegram_connected'));
    }

    public function sendTelegramTest(TelegramNotifier $telegram): void
    {
        $this->assertOwner();
        $workspace = $this->workspace();

        if (! $workspace->telegramConnected()) {
            return;
        }

        RateLimits::hitOrFail('telegram:'.$workspace->id, 8, 60, 'telegramToken');

        try {
            $telegram->send(
                $workspace->telegram_bot_token,
                $workspace->telegram_chat_id,
                __('app.reminders.telegram_test_body'),
            );
            $this->toast(__('app.flash.telegram_test_sent'));
        } catch (\Throwable $e) {
            report($e);
            $this->addError('telegramToken', __('app.reminders.telegram_failed'));
        }
    }

    public function disconnectTelegram(): void
    {
        $this->assertOwner();
        $this->workspace()->forceFill([
            'telegram_bot_token' => null,
            'telegram_bot_username' => null,
            'telegram_chat_id' => null,
            'telegram_chat_title' => null,
            'telegram_connected_at' => null,
        ])->save();

        $this->reset(['telegramToken', 'telegramChatId', 'telegramChats']);
        $this->toast(__('app.flash.telegram_disconnected'));
    }

    public function runNow(ReminderDispatcher $dispatcher): void
    {
        $this->assertOwner();
        $sent = $dispatcher->dispatchForWorkspace($this->workspace());
        $this->toast(
            $sent === 0
                ? __('app.reminders.run_empty')
                : __('app.reminders.run_sent', ['count' => $sent]),
        );
    }

    public function render()
    {
        return view('livewire.settings.reminders', [
            'workspace' => $this->workspace(),
        ])->title(__('app.titles.reminders'));
    }

    private function validateTelegramToken(): void
    {
        $this->validate($this->telegramTokenRules());
    }

    private function telegramTokenRules(): array
    {
        return [
            'telegramToken' => ['required', 'string', 'max:128', 'regex:/^\d+:[A-Za-z0-9_-]+$/'],
        ];
    }
}
