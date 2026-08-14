<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'name', 'email', 'notes'])]
class Client extends Model
{
    use BelongsToWorkspace, HasFactory;

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
