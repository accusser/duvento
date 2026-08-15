<?php

namespace App\Notifications;

use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssetExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Asset $asset,
        public int $daysBefore,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = $this->asset->days_left;

        return (new MailMessage)
            ->subject(__('app.mail.expiring_subject', ['name' => $this->asset->name, 'days' => $days]))
            ->line(__('app.mail.expiring_line', [
                'name' => $this->asset->name,
                'type' => $this->asset->assetType?->displayLabel(),
                'date' => $this->asset->expires_at?->toDateString(),
            ]))
            ->line(__('app.mail.expiring_client', ['client' => $this->asset->client?->name ?? __('app.common.empty')]))
            ->line(__('app.mail.expiring_days', ['days' => $days]))
            ->line(__('app.mail.expiring_before', ['days' => $this->daysBefore]));
    }
}
