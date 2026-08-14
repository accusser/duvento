<?php

namespace App\Support;

use App\Enums\ReminderChannel;
use App\Models\Asset;
use App\Models\ReminderDispatch;
use App\Models\Workspace;
use App\Notifications\AssetExpiringNotification;
use App\Support\ActivityLogger;
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

                $already = ReminderDispatch::query()->where([
                    'asset_id' => $asset->id,
                    'days_before' => $rule->days_before,
                    'channel' => ReminderChannel::Email->value,
                    'sent_on' => now()->toDateString(),
                ])->exists();

                if ($already) {
                    continue;
                }

                $email = $asset->notice_email ?: $asset->client?->email ?: $workspace->users()->first()?->email;

                if (! $email) {
                    continue;
                }

                Notification::route('mail', $email)
                    ->notify(new AssetExpiringNotification($asset, $rule->days_before));

                ReminderDispatch::query()->create([
                    'asset_id' => $asset->id,
                    'days_before' => $rule->days_before,
                    'channel' => ReminderChannel::Email,
                    'sent_on' => now()->toDateString(),
                ]);

                ActivityLogger::log($workspace, 'reminder.sent', $asset, [
                    'days_before' => $rule->days_before,
                    'email' => $email,
                ]);

                $sent++;
            }
        });

        return $sent;
    }
}
