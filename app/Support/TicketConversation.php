<?php

namespace App\Support;

use App\Enums\TicketAuthorType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\AdminUser;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TicketClientMessageNotification;
use App\Notifications\TicketReplyNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class TicketConversation
{
    public function create(
        Workspace $workspace,
        User $user,
        string $subject,
        string $body,
        ?UploadedFile $attachment = null,
        TicketPriority $priority = TicketPriority::Normal,
    ): Ticket {
        if ($attachment !== null) {
            TicketAttachmentSecurity::validate($attachment);
        }

        $ticket = DB::transaction(function () use ($workspace, $user, $subject, $body, $priority): Ticket {
            $ticket = Ticket::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'subject' => trim($subject),
                'status' => TicketStatus::Open,
                'priority' => $priority,
                'last_message_at' => now(),
            ]);

            $ticket->messages()->create([
                'author_type' => TicketAuthorType::Client,
                'author_id' => $user->id,
                'body' => trim($body),
            ]);

            return $ticket;
        });

        $message = $ticket->messages()->latest('id')->firstOrFail();
        $this->attach($message, $attachment);
        $this->notifyAdmins($message);

        return $ticket->fresh();
    }

    public function replyAsClient(
        Ticket $ticket,
        User $user,
        string $body,
        ?UploadedFile $attachment = null,
    ): TicketMessage {
        if ($attachment !== null) {
            TicketAttachmentSecurity::validate($attachment);
        }

        $message = DB::transaction(function () use ($ticket, $user, $body): TicketMessage {
            $message = $ticket->messages()->create([
                'author_type' => TicketAuthorType::Client,
                'author_id' => $user->id,
                'body' => trim($body),
            ]);

            $ticket->forceFill([
                'status' => $ticket->status === TicketStatus::Closed ? TicketStatus::Open : $ticket->status,
                'last_message_at' => $message->created_at,
            ])->save();

            return $message;
        });

        $this->attach($message, $attachment);
        $this->notifyAdmins($message);

        return $message;
    }

    public function replyAsAdmin(
        Ticket $ticket,
        AdminUser $admin,
        string $body,
        ?UploadedFile $attachment = null,
    ): TicketMessage {
        if ($attachment !== null) {
            TicketAttachmentSecurity::validate($attachment);
        }

        $message = DB::transaction(function () use ($ticket, $admin, $body): TicketMessage {
            $message = $ticket->messages()->create([
                'author_type' => TicketAuthorType::Admin,
                'author_id' => $admin->id,
                'body' => trim($body),
            ]);

            $ticket->forceFill([
                'assigned_to' => $ticket->assigned_to ?? $admin->id,
                'status' => $ticket->status === TicketStatus::Open
                    ? TicketStatus::InProgress
                    : $ticket->status,
                'last_message_at' => $message->created_at,
            ])->save();

            return $message;
        });

        $this->attach($message, $attachment);

        try {
            $ticket->user->notify(new TicketReplyNotification($message->load('ticket')));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $message;
    }

    /** Removes the ticket with its messages and stored files. */
    public function purge(Ticket $ticket): void
    {
        $ticket->loadMissing('messages.attachments');

        DB::transaction(function () use ($ticket): void {
            $ticket->messages
                ->flatMap->attachments
                ->each->delete();

            $ticket->delete();
        });
    }

    public function markReadByClient(Ticket $ticket): void
    {
        $ticket->messages()
            ->where('author_type', TicketAuthorType::Admin->value)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markReadByAdmin(Ticket $ticket): void
    {
        $ticket->messages()
            ->where('author_type', TicketAuthorType::Client->value)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function attach(TicketMessage $message, ?UploadedFile $file): void
    {
        if ($file === null) {
            return;
        }

        $extension = strtolower($file->extension());
        $fileName = Str::uuid().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs(
            "tickets/{$message->ticket->workspace_id}/{$message->ticket_id}",
            $fileName,
            'local',
        );

        if ($path === false) {
            throw new RuntimeException('Unable to store the ticket attachment.');
        }

        try {
            $message->attachments()->create([
                'disk' => 'local',
                'path' => $path,
                'file_name' => TicketAttachmentSecurity::safeOriginalName($file),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }

    private function notifyAdmins(TicketMessage $message): void
    {
        $admins = AdminUser::query()->whereNull('blocked_at')->get();

        try {
            Notification::send(
                $admins,
                new TicketClientMessageNotification($message->load('ticket.workspace', 'ticket.user')),
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
