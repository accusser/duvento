<?php

namespace App\Models;

use App\Support\AppLocale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'key', 'label', 'icon', 'default_reminder_days'])]
class AssetType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_reminder_days' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function isSystem(): bool
    {
        return $this->workspace_id === null;
    }

    public function displayLabel(): string
    {
        if ($this->isSystem()) {
            $key = 'app.asset_types.'.$this->key;

            if (trans()->has($key)) {
                return __($key);
            }
        }

        return (string) $this->label;
    }

    public function matchesName(string $value): bool
    {
        $needle = mb_strtolower(trim($value));
        $aliases = [mb_strtolower($this->key), mb_strtolower($this->label)];

        if ($this->isSystem()) {
            foreach (AppLocale::codes() as $locale) {
                $aliases[] = mb_strtolower(__('app.asset_types.'.$this->key, [], $locale));
            }
        }

        return in_array($needle, array_unique($aliases), true);
    }

    public function scopeAvailableFor(Builder $query, Workspace|int $workspace): Builder
    {
        $id = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $query->where(fn (Builder $q) => $q
            ->whereNull('workspace_id')
            ->orWhere('workspace_id', $id));
    }
}
