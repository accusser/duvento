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
            ->subject("Duvento: {$this->asset->name} истекает через {$days} дн.")
            ->line("Актив «{$this->asset->name}» ({$this->asset->assetType?->label}) истекает {$this->asset->expires_at?->toDateString()}.")
            ->line('Клиент: '.($this->asset->client?->name ?? '—'))
            ->line('Осталось дней: '.$days)
            ->line('Это напоминание за '.$this->daysBefore.' дн. до дедлайна.');
    }
}
