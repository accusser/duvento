<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'name', 'token_hash', 'last_used_at'])]
#[Hidden(['token_hash'])]
class WorkspaceApiToken extends Model
{
    use BelongsToWorkspace;

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public static function generatePlain(): string
    {
        return 'dvnt_'.bin2hex(random_bytes(20));
    }
}
