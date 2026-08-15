<?php

namespace App\Models;

use App\Enums\WorkspacePlan;
use App\Enums\WorkspaceRole;
use App\Support\Edition;
use App\Support\UpcomingPayments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'plan', 'blocked_at', 'onboarding_done', 'currency'])]
#[Hidden(['telegram_bot_token'])]
class Workspace extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'plan' => WorkspacePlan::class,
            'blocked_at' => 'datetime',
            'onboarding_done' => 'array',
            'telegram_bot_token' => 'encrypted',
            'telegram_connected_at' => 'datetime',
        ];
    }

    public function telegramConnected(): bool
    {
        return filled($this->telegram_bot_token) && filled($this->telegram_chat_id);
    }

    public function currencyCode(): string
    {
        return UpcomingPayments::normalizeCurrency($this->currency);
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

    public function workspaceReminderRules(): HasMany
    {
        return $this->hasMany(ReminderRule::class)->whereNull('asset_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketMessages(): HasManyThrough
    {
        return $this->hasManyThrough(TicketMessage::class, Ticket::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(WorkspaceApiToken::class);
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function attachOwner(User $user): void
    {
        $this->users()->attach($user->id, ['role' => WorkspaceRole::Owner->value]);
        $user->forceFill(['current_workspace_id' => $this->id])->save();
    }

    public function isLastOwner(User $user): bool
    {
        if (! $user->isOwnerOf($this)) {
            return false;
        }

        return $this->users()->wherePivot('role', WorkspaceRole::Owner->value)->count() <= 1;
    }

    public function trialEndsAt(): ?Carbon
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
