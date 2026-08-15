<?php

namespace App\Support;

use App\Enums\ReminderChannel;
use App\Models\Asset;
use App\Models\ReminderDispatch;
use App\Models\Workspace;
use App\Notifications\AssetExpiringNotification;
use Illuminate\Support\Facades\Notification;

final class ReminderDispatcher
{
    public function dispatchForWorkspace(Workspace $workspace): int
    {
        $sent = 0;

        $workspace->assets()->with(['client', 'assetType', 'workspace', 'reminderRules'])->each(function (Asset $asset) use ($workspace, &$sent) {
            $days = $asset->days_left;

            if ($days === null || $days < 0) {
                return;
            }

            $rules = $asset->effectiveReminderRules()
                ->filter(fn ($rule) => $rule->channel === ReminderChannel::Email);

            foreach ($rules as $rule) {
                if ($days !== $rule->days_before) {
                    continue;
                }

                if ($this->sendEmail($workspace, $asset, $rule->days_before)) {
                    $sent++;
                }

                if ($this->sendTelegram($workspace, $asset, $rule->days_before)) {
                    $sent++;
                }
            }
        });

        return $sent;
    }

    private function sendEmail(Workspace $workspace, Asset $asset, int $daysBefore): bool
    {
        $email = $asset->notice_email ?: $asset->client?->email ?: $workspace->users()->first()?->email;

        if (! $email) {
            return false;
        }

        $dispatch = $this->claim($asset, $daysBefore, ReminderChannel::Email);

        if (! $dispatch) {
            return false;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new AssetExpiringNotification($asset, $daysBefore));
        } catch (\Throwable $e) {
            $dispatch->delete();

            throw $e;
        }

        $this->recordActivity($workspace, $asset, $daysBefore, ReminderChannel::Email, ['email' => $email]);

        return true;
    }

    private function sendTelegram(Workspace $workspace, Asset $asset, int $daysBefore): bool
    {
        if (! $workspace->telegramConnected()) {
            return false;
        }

        $dispatch = $this->claim($asset, $daysBefore, ReminderChannel::Telegram);

        if (! $dispatch) {
            return false;
        }

        try {
            app(TelegramNotifier::class)->send(
                $workspace->telegram_bot_token,
                $workspace->telegram_chat_id,
                $this->telegramText($asset, $daysBefore),
            );
        } catch (\Throwable $e) {
            $dispatch->delete();
            report($e);

            return false;
        }

        $this->recordActivity($workspace, $asset, $daysBefore, ReminderChannel::Telegram, [
            'chat_id' => $workspace->telegram_chat_id,
        ]);

        return true;
    }

    private function claim(Asset $asset, int $daysBefore, ReminderChannel $channel): ?ReminderDispatch
    {
        $dispatch = ReminderDispatch::query()->firstOrCreate([
            'asset_id' => $asset->id,
            'days_before' => $daysBefore,
            'channel' => $channel->value,
            'sent_on' => today(),
        ]);

        return $dispatch->wasRecentlyCreated ? $dispatch : null;
    }

    private function recordActivity(Workspace $workspace, Asset $asset, int $daysBefore, ReminderChannel $channel, array $extra): void
    {
        ActivityLogger::log($workspace, 'reminder.sent', $asset, [
            'name' => $asset->name,
            'days_before' => $daysBefore,
            'channel' => $channel->value,
            ...$extra,
        ]);
    }

    private function telegramText(Asset $asset, int $daysBefore): string
    {
        $days = $asset->days_left;

        return collect([
            __('app.mail.expiring_line', [
                'name' => $asset->name,
                'type' => $asset->assetType?->displayLabel(),
                'date' => $asset->expires_at?->toDateString(),
            ]),
            __('app.mail.expiring_client', ['client' => $asset->client?->name ?? __('app.common.empty')]),
            __('app.mail.expiring_days', ['days' => $days]),
            __('app.mail.expiring_before', ['days' => $daysBefore]),
        ])->implode("\n");
    }
}
