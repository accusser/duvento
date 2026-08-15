<?php

namespace App\Notifications;

use App\Models\TicketMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketReplyNotification extends Notification
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
            ->subject(__('app.mail.ticket_reply_subject', ['subject' => $ticket->subject]))
            ->line(__('app.mail.ticket_reply_line', ['subject' => $ticket->subject]))
            ->line(Str::limit($this->message->body, 240))
            ->action(__('app.mail.ticket_reply_action'), route('support.show', $ticket))
            ->line(__('app.mail.ticket_reply_ignore'));
    }
}
