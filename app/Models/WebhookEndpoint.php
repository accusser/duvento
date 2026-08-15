<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'url', 'secret', 'events', 'active'])]
#[Hidden(['secret'])]
class WebhookEndpoint extends Model
{
    use BelongsToWorkspace;

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'active' => 'boolean',
        ];
    }

    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(24));
    }
}
