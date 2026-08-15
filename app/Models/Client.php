<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Rules\HttpWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['workspace_id', 'name', 'contact_name', 'email', 'website', 'notes'])]
class Client extends Model
{
    use BelongsToWorkspace, HasFactory;

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function publicUrl(): ?string
    {
        return $this->public_token ? route('share.show', $this->public_token) : null;
    }

    public function issuePublicToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (self::query()->where('public_token', $token)->exists());

        $this->forceFill(['public_token' => $token])->save();

        return $token;
    }

    public function revokePublicToken(): void
    {
        $this->forceFill(['public_token' => null])->save();
    }

    public function websiteHref(): ?string
    {
        return HttpWebsite::normalize($this->website);
    }
}
