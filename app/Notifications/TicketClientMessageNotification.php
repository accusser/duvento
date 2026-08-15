<?php

namespace App\Notifications;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\TicketMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketClientMessageNotification extends Notification
{
    public function __construct(public TicketMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->message->ticket;

        return (new MailMessage)
            ->subject(__('app.mail.ticket_client_subject', ['subject' => $ticket->subject]))
            ->line(__('app.mail.ticket_client_line', [
                'workspace' => $ticket->workspace->name,
                'user' => $ticket->user->name,
            ]))
            ->line(Str::limit($this->message->body, 240))
            ->action(__('app.mail.ticket_client_action'), TicketResource::getUrl('view', ['record' => $ticket]));
    }
}
