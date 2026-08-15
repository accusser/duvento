<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInviteNotification extends Notification
{
    public function __construct(public WorkspaceInvitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('invites.show', $this->invitation->token);

        return (new MailMessage)
            ->subject(__('app.mail.invite_subject', ['workspace' => $this->invitation->workspace->name]))
            ->line(__('app.mail.invite_line', ['workspace' => $this->invitation->workspace->name]))
            ->action(__('app.mail.invite_action'), $url)
            ->line(__('app.mail.invite_ignore'));
    }
}
