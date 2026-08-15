<?php

namespace App\Livewire\Support;

use App\Enums\TicketStatus;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Ticket;
use App\Support\TicketAttachmentSecurity;
use App\Support\TicketConversation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Show extends Component
{
    use InteractsWithWorkspace;
    use WithFileUploads;

    #[Locked]
    public int $ticketId;

    public string $body = '';

    public $attachment = null;

    public function mount(int $ticket, TicketConversation $conversation): void
    {
        $record = $this->workspace()->tickets()->findOrFail($ticket);
        $this->ticketId = $record->id;
        $conversation->markReadByClient($record);
    }

    public function reply(TicketConversation $conversation): void
    {
        $this->body = trim($this->body);

        $validated = $this->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => TicketAttachmentSecurity::rules(),
        ]);

        $conversation->replyAsClient(
            $this->ticket(),
            auth()->user(),
            $validated['body'],
            $this->attachment,
        );
        $this->reset('body', 'attachment');
        $this->toast(__('app.flash.ticket_sent'));
    }

    public function closeTicket(): void
    {
        $ticket = $this->ticket();
        $ticket->update(['status' => TicketStatus::Closed]);
        $this->toast(__('app.flash.ticket_closed'));
    }

    public function deleteTicket(TicketConversation $conversation): void
    {
        $conversation->purge($this->ticket());

        $this->flashToast(__('app.flash.ticket_deleted'));
        $this->redirectRoute('support', navigate: true);
    }

    public function render()
    {
        $ticket = $this->ticket()->load(['messages.author', 'messages.attachments', 'user', 'workspace']);
        app(TicketConversation::class)->markReadByClient($ticket);

        return view('livewire.support.show', compact('ticket'))
            ->title($ticket->subject);
    }

    private function ticket(): Ticket
    {
        return $this->workspace()->tickets()->findOrFail($this->ticketId);
    }
}
