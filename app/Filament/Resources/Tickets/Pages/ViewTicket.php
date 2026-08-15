<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Support\TicketAttachmentSecurity;
use App\Support\TicketConversation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

class ViewTicket extends ViewRecord
{
    use WithFileUploads;

    protected static string $resource = TicketResource::class;

    protected string $view = 'filament.resources.tickets.view';

    public string $body = '';

    public $attachment = null;

    public string $status = '';

    public string $priority = '';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Ticket $ticket */
        $ticket = $this->record;
        app(TicketConversation::class)->markReadByAdmin($ticket);
        $this->status = $ticket->status->value;
        $this->priority = $ticket->priority->value;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->subject;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label(__('admin.tickets.delete'))
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription(__('admin.tickets.delete_confirm'))
                ->action(function (TicketConversation $conversation): void {
                    $conversation->purge($this->record);

                    Notification::make()
                        ->title(__('admin.tickets.deleted'))
                        ->success()
                        ->send();

                    $this->redirect(TicketResource::getUrl('index'));
                }),
        ];
    }

    public function reply(TicketConversation $conversation): void
    {
        $this->body = trim($this->body);

        $validated = $this->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => TicketAttachmentSecurity::rules(),
        ]);

        $conversation->replyAsAdmin(
            $this->record,
            auth('admin')->user(),
            $validated['body'],
            $this->attachment,
        );
        $this->reset('body', 'attachment');
        $this->record->refresh();

        Notification::make()
            ->title(__('admin.tickets.reply_sent'))
            ->success()
            ->send();
    }

    public function updateMeta(): void
    {
        $validated = $this->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ]);

        $this->record->update($validated);
        $this->record->refresh();

        Notification::make()
            ->title(__('admin.tickets.updated'))
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'statusOptions' => TicketStatus::options(),
            'priorityOptions' => TicketPriority::options(),
        ];
    }
}
