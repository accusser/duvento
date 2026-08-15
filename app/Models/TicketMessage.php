<?php

namespace App\Models;

use App\Enums\TicketAuthorType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['ticket_id', 'author_type', 'author_id', 'body', 'read_at', 'dismissed_at'])]
class TicketMessage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'author_type', 'author_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function authorType(): TicketAuthorType
    {
        return TicketAuthorType::from((string) $this->author_type);
    }

    public function authorName(): string
    {
        return match ($this->authorType()) {
            TicketAuthorType::Client => $this->author?->name ?? __('app.support.deleted_user'),
            TicketAuthorType::Admin => $this->author?->name ?? __('app.support.support_team'),
        };
    }
}
