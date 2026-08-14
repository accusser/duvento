<?php

namespace App\Models;

use App\Enums\WorkspacePlan;
use App\Enums\WorkspaceRole;
use App\Support\Edition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'plan', 'blocked_at'])]
class Workspace extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'plan' => WorkspacePlan::class,
            'blocked_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function assetTypes(): HasMany
    {
        return $this->hasMany(AssetType::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function reminderRules(): HasMany
    {
        return $this->hasMany(ReminderRule::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function attachOwner(User $user): void
    {
        $this->users()->attach($user->id, ['role' => WorkspaceRole::Owner->value]);
        $user->forceFill(['current_workspace_id' => $this->id])->save();
    }

    public function trialEndsAt(): ?\Illuminate\Support\Carbon
    {
        return $this->subscriptions()->latest()->first()?->trial_ends_at;
    }

    public function trialExpired(): bool
    {
        if (! Edition::isCloud() || $this->plan !== WorkspacePlan::FreeTrial) {
            return false;
        }

        $ends = $this->trialEndsAt();

        return $ends !== null && $ends->isPast();
    }
}
