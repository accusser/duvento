<?php

namespace App\Models;

use App\Enums\WorkspacePlan;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'type', 'plan', 'amount', 'provider_id', 'payload'])]
class PaymentEvent extends Model
{
    use BelongsToWorkspace;

    protected function casts(): array
    {
        return [
            'plan' => WorkspacePlan::class,
            'payload' => 'array',
            'amount' => 'integer',
        ];
    }
}
