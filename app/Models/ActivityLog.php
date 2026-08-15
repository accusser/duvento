<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['workspace_id', 'user_id', 'admin_user_id', 'action', 'subject_type', 'subject_id', 'properties', 'read_at', 'dismissed_at'])]
class ActivityLog extends Model
{
    use BelongsToWorkspace, HasFactory;

    public const ALERTS = ['reminder.sent', 'ssl.check_failed'];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'read_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function scopeInbox(Builder $query): Builder
    {
        return $query->whereIn('action', self::ALERTS)->whereNull('dismissed_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    public function actorName(): ?string
    {
        return $this->adminUser?->name ?? $this->user?->name;
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
