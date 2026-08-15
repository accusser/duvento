<?php

namespace App\Filament\Pages;

use App\Enums\TicketAuthorType;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\ActivityLog;
use App\Models\TicketMessage;
use App\Support\ActivityAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class AdminNotifications extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'notifications';

    protected string $view = 'filament.pages.admin-notifications';

    public function getTitle(): string|Htmlable
    {
        return __('admin.notifications.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('admin.notifications.description');
    }

    public function markRead(string $type, int $id): void
    {
        $this->query($type)->whereKey($id)->update(['read_at' => now()]);
    }

    public function markAll(): void
    {
        ActivityLog::query()->inbox()->whereNull('read_at')->update(['read_at' => now()]);
        $this->ticketMessages()->whereNull('read_at')->update(['read_at' => now()]);

        Notification::make()
            ->title(__('admin.notifications.marked_all'))
            ->success()
            ->send();
    }

    public function delete(string $type, int $id): void
    {
        $this->query($type)->whereKey($id)->update(['dismissed_at' => now()]);

        Notification::make()
            ->title(__('admin.notifications.deleted'))
            ->success()
            ->send();
    }

    public function clear(): void
    {
        ActivityLog::query()->inbox()->update(['dismissed_at' => now()]);
        $this->ticketMessages()->update(['dismissed_at' => now()]);

        Notification::make()
            ->title(__('admin.notifications.cleared'))
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $alerts = ActivityLog::query()
            ->inbox()
            ->with('workspace')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (ActivityLog $log): array => [
                'type' => 'activity',
                'id' => $log->id,
                'title' => ActivityAction::label($log->action),
                'body' => $log->properties['name'] ?? $log->workspace?->name,
                'meta' => $log->workspace?->name,
                'created_at' => $log->created_at,
                'read' => $log->read_at !== null,
                'url' => ActivityLogResource::getUrl('view', ['record' => $log]),
                'icon' => 'mdi-bell-outline',
            ]);

        $tickets = $this->ticketMessages()
            ->with(['ticket.workspace', 'ticket.user'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (TicketMessage $message): array => [
                'type' => 'ticket',
                'id' => $message->id,
                'title' => $message->ticket->subject,
                'body' => Str::limit($message->body, 140),
                'meta' => collect([$message->ticket->workspace?->name, $message->ticket->user?->name])
                    ->filter()
                    ->join(' · '),
                'created_at' => $message->created_at,
                'read' => $message->read_at !== null,
                'url' => TicketResource::getUrl('view', ['record' => $message->ticket]),
                'icon' => 'mdi-lifebuoy',
            ]);

        return [
            'notifications' => $alerts
                ->concat($tickets)
                ->sortByDesc('created_at')
                ->values()
                ->take(100),
        ];
    }

    private function query(string $type)
    {
        abort_unless(in_array($type, ['activity', 'ticket'], true), 404);

        return $type === 'activity'
            ? ActivityLog::query()->inbox()
            : $this->ticketMessages();
    }

    private function ticketMessages()
    {
        return TicketMessage::query()
            ->where('author_type', TicketAuthorType::Client->value)
            ->whereNull('dismissed_at');
    }
}
