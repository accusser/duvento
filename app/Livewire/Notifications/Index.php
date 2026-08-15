<?php

namespace App\Livewire\Notifications;

use App\Enums\TicketAuthorType;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\ActivityLog;
use App\Models\TicketMessage;
use App\Support\ActivityAction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public function markRead(int $id, string $type = 'activity'): void
    {
        $this->query($type)->whereKey($id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function markAll(): void
    {
        $this->alerts()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $this->ticketMessages()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function delete(int $id, string $type = 'activity'): void
    {
        $this->query($type)->whereKey($id)->update(['dismissed_at' => now()]);
        $this->toast(__('app.flash.notification_deleted'), 'delete');
    }

    public function clear(): void
    {
        $this->alerts()->update(['dismissed_at' => now()]);
        $this->ticketMessages()->update(['dismissed_at' => now()]);
        $this->toast(__('app.flash.notifications_cleared'), 'delete');
    }

    public function render()
    {
        $entries = $this->alerts()
            ->latest()
            ->get()
            ->map(fn (ActivityLog $log): array => [
                'type' => 'activity',
                'id' => $log->id,
                'title' => ActivityAction::label($log->action),
                'body' => $log->properties['name'] ?? null,
                'created_at' => $log->created_at,
                'read' => $log->read_at !== null,
                'url' => null,
                'icon' => 'mdi-bell-outline',
            ])
            ->concat(
                $this->ticketMessages()
                    ->with('ticket')
                    ->latest()
                    ->get()
                    ->map(fn (TicketMessage $message): array => [
                        'type' => 'ticket',
                        'id' => $message->id,
                        'title' => $message->ticket->subject,
                        'body' => Str::limit($message->body, 140),
                        'created_at' => $message->created_at,
                        'read' => $message->read_at !== null,
                        'url' => route('support.show', $message->ticket),
                        'icon' => 'mdi-lifebuoy',
                    ]),
            )
            ->sortByDesc('created_at')
            ->values();

        $page = $this->getPage();
        $perPage = 30;
        $notifications = new LengthAwarePaginator(
            $entries->forPage($page, $perPage),
            $entries->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return view('livewire.notifications.index', compact('notifications'))->title(__('app.titles.notifications'));
    }

    private function alerts()
    {
        return $this->workspace()->activityLogs()->inbox();
    }

    private function ticketMessages()
    {
        return TicketMessage::query()
            ->where('author_type', TicketAuthorType::Admin->value)
            ->whereNull('dismissed_at')
            ->whereHas('ticket', fn ($query) => $query->where('workspace_id', $this->workspace()->id));
    }

    private function query(string $type)
    {
        abort_unless(in_array($type, ['activity', 'ticket'], true), 404);

        return $type === 'activity' ? $this->alerts() : $this->ticketMessages();
    }
}
