<?php

namespace App\Models;

use App\Enums\ReminderChannel;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'asset_id', 'days_before', 'channel'])]
class ReminderRule extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected function casts(): array
    {
        return [
            'days_before' => 'integer',
            'channel' => ReminderChannel::class,
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function isOverride(): bool
    {
        return $this->asset_id !== null;
    }
}
