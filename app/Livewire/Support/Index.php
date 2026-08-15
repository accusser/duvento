<?php

namespace App\Livewire\Support;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\TicketAttachmentSecurity;
use App\Support\TicketConversation;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;
    use WithFileUploads;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $status = 'all';

    public bool $showCreate = false;

    public string $subject = '';

    public string $body = '';

    public string $priority = TicketPriority::Normal->value;

    public $attachment = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function create(TicketConversation $conversation): void
    {
        $this->subject = trim($this->subject);
        $this->body = trim($this->body);

        $validated = $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'attachment' => TicketAttachmentSecurity::rules(),
        ]);

        $ticket = $conversation->create(
            $this->workspace(),
            auth()->user(),
            $validated['subject'],
            $validated['body'],
            $this->attachment,
            TicketPriority::from($validated['priority']),
        );

        $this->flashToast(__('app.flash.ticket_created'));
        $this->redirectRoute('support.show', $ticket, navigate: true);
    }

    public function deleteTicket(int $ticket, TicketConversation $conversation): void
    {
        $conversation->purge($this->workspace()->tickets()->findOrFail($ticket));

        $this->resetPage();
        $this->toast(__('app.flash.ticket_deleted'));
    }

    public function render()
    {
        $query = $this->workspace()->tickets()
            ->with('user')
            ->withActivityCounts()
            ->orderByDesc('last_message_at');

        if ($status = TicketStatus::tryFrom($this->status)) {
            $query->where('status', $status);
        }

        return view('livewire.support.index', [
            'tickets' => $query->paginate(20),
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
        ])->title(__('app.titles.support'));
    }
}
