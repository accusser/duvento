<?php

namespace App\Models;

use App\Enums\TicketAuthorType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'user_id',
    'subject',
    'status',
    'priority',
    'assigned_to',
    'last_message_at',
])]
class Ticket extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function isUrgent(): bool
    {
        return $this->priority === TicketPriority::High;
    }

    /** Counts used by ticket lists on both sides: replies and unread messages per author. */
    public function scopeWithActivityCounts(Builder $query): Builder
    {
        return $query->withCount([
            'messages as admin_replies_count' => fn (Builder $messages) => $messages
                ->where('author_type', TicketAuthorType::Admin->value),
            'messages as client_messages_count' => fn (Builder $messages) => $messages
                ->where('author_type', TicketAuthorType::Client->value),
            'messages as unread_admin_count' => fn (Builder $messages) => $messages
                ->where('author_type', TicketAuthorType::Admin->value)
                ->whereNull('read_at'),
            'messages as unread_client_count' => fn (Builder $messages) => $messages
                ->where('author_type', TicketAuthorType::Client->value)
                ->whereNull('read_at'),
        ]);
    }

    public function scopeUnreadFromClients(Builder $query): Builder
    {
        return $query->whereHas('messages', fn (Builder $messages) => $messages
            ->where('author_type', TicketAuthorType::Client->value)
            ->whereNull('read_at')
            ->whereNull('dismissed_at'));
    }

    public function scopeUnreadForClient(Builder $query): Builder
    {
        return $query->whereHas('messages', fn (Builder $messages) => $messages
            ->where('author_type', TicketAuthorType::Admin->value)
            ->whereNull('read_at')
            ->whereNull('dismissed_at'));
    }
}
