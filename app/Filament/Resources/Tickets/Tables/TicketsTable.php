<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Support\AdminFilters;
use App\Models\Ticket;
use App\Support\TicketConversation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withActivityCounts())
            ->columns([
                TextColumn::make('unread')
                    ->label(__('admin.tickets.unread'))
                    ->badge()
                    ->state(fn (Ticket $record): string => $record->unread_client_count > 0
                        ? (string) $record->unread_client_count
                        : '—')
                    ->icon(fn (Ticket $record): Heroicon => $record->unread_client_count > 0
                        ? Heroicon::Envelope
                        : Heroicon::OutlinedEnvelopeOpen)
                    ->color(fn (Ticket $record): string => $record->unread_client_count > 0 ? 'danger' : 'gray')
                    ->tooltip(fn (Ticket $record): string => $record->unread_client_count > 0
                        ? __('admin.tickets.unread')
                        : __('admin.tickets.read')),
                TextColumn::make('replies')
                    ->label(__('admin.tickets.replies'))
                    ->badge()
                    ->state(fn (Ticket $record): string => $record->admin_replies_count > 0
                        ? (string) $record->admin_replies_count
                        : '—')
                    ->icon(fn (Ticket $record): Heroicon => $record->admin_replies_count > 0
                        ? Heroicon::ChatBubbleLeftRight
                        : Heroicon::OutlinedClock)
                    ->color(fn (Ticket $record): string => $record->admin_replies_count > 0 ? 'success' : 'warning')
                    ->tooltip(fn (Ticket $record): string => $record->admin_replies_count > 0
                        ? __('admin.tickets.replies')
                        : __('admin.tickets.no_reply')),
                TextColumn::make('workspace.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('admin.fields.workspace')),
                TextColumn::make('subject')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where(function (Builder $query) use ($search): void {
                            $query->where('subject', 'like', "%{$search}%")
                                ->orWhereHas('messages', fn (Builder $messages) => $messages
                                    ->where('body', 'like', "%{$search}%"));
                        }))
                    ->limit(60)
                    ->label(__('admin.tickets.subject')),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TicketStatus $state): string => $state->label())
                    ->icon(fn (TicketStatus $state): Heroicon => match ($state) {
                        TicketStatus::Open => Heroicon::EnvelopeOpen,
                        TicketStatus::InProgress => Heroicon::ArrowPath,
                        TicketStatus::Closed => Heroicon::CheckCircle,
                    })
                    ->color(fn (TicketStatus $state): string => match ($state) {
                        TicketStatus::Open => 'success',
                        TicketStatus::InProgress => 'warning',
                        TicketStatus::Closed => 'gray',
                    })
                    ->label(__('admin.fields.status')),
                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (TicketPriority $state): string => $state->label())
                    ->icon(fn (TicketPriority $state): Heroicon => match ($state) {
                        TicketPriority::Low => Heroicon::ArrowDown,
                        TicketPriority::Normal => Heroicon::Flag,
                        TicketPriority::High => Heroicon::Fire,
                    })
                    ->color(fn (TicketPriority $state): string => match ($state) {
                        TicketPriority::Low => 'gray',
                        TicketPriority::Normal => 'info',
                        TicketPriority::High => 'danger',
                    })
                    ->tooltip(fn (TicketPriority $state): ?string => $state === TicketPriority::High
                        ? __('admin.tickets.urgent')
                        : null)
                    ->label(__('admin.tickets.priority')),
                TextColumn::make('last_message_at')
                    ->since()
                    ->sortable()
                    ->label(__('admin.tickets.last_message')),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                AdminFilters::ticketStatus(),
                AdminFilters::workspace(),
                AdminFilters::ticketPriority(),
                Filter::make('unread')
                    ->label(__('admin.tickets.only_unread'))
                    ->query(fn (Builder $query): Builder => $query->unreadFromClients()),
            ])
            ->recordUrl(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('delete')
                    ->label(__('admin.tickets.delete'))
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalDescription(__('admin.tickets.delete_confirm'))
                    ->action(function (Ticket $record): void {
                        app(TicketConversation::class)->purge($record);

                        Notification::make()
                            ->title(__('admin.tickets.deleted'))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
