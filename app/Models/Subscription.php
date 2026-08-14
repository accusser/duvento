<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\WorkspacePlan;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'workspace_id',
    'plan',
    'status',
    'billing_provider_id',
    'trial_ends_at',
    'ends_at',
])]
class Subscription extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected function casts(): array
    {
        return [
            'plan' => WorkspacePlan::class,
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
