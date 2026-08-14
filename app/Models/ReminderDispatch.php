<?php

namespace App\Models;

use App\Enums\ReminderChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['asset_id', 'days_before', 'channel', 'sent_on'])]
class ReminderDispatch extends Model
{
    protected function casts(): array
    {
        return [
            'days_before' => 'integer',
            'channel' => ReminderChannel::class,
            'sent_on' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
