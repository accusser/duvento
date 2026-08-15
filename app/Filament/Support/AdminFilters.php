<?php

namespace App\Filament\Support;

use App\Enums\AssetStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\AdminUser;
use App\Models\AssetType;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ActivityAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class AdminFilters
{
    public static function workspace(string $name = 'workspace_id'): SelectFilter
    {
        return SelectFilter::make($name)
            ->label(__('admin.fields.workspace'))
            ->options(fn (): array => Workspace::query()->orderBy('name')->pluck('name', 'id')->all())
            ->preload();
    }

    public static function userWorkspaces(): SelectFilter
    {
        return SelectFilter::make('workspaces')
            ->label(__('admin.fields.workspace'))
            ->options(fn (): array => Workspace::query()->orderBy('name')->pluck('name', 'id')->all())
            ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                ? $query->whereHas('workspaces', fn (Builder $workspaces) => $workspaces->whereKey($data['value']))
                : $query)
            ->preload();
    }

    public static function assetType(): SelectFilter
    {
        return SelectFilter::make('asset_type_id')
            ->label(__('admin.fields.type'))
            ->options(fn (): array => AssetType::query()
                ->with('workspace')
                ->get()
                ->sortBy(fn (AssetType $type) => mb_strtolower($type->displayLabel()))
                ->mapWithKeys(function (AssetType $type): array {
                    $label = $type->displayLabel();

                    if (filled($type->workspace?->name)) {
                        $label .= ' · '.$type->workspace->name;
                    }

                    return [$type->id => $label];
                })
                ->all())
            ->preload();
    }

    public static function assetStatus(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('admin.fields.status'))
            ->options(collect(AssetStatus::cases())->mapWithKeys(
                fn (AssetStatus $status) => [$status->value => $status->label()]
            )->all())
            ->query(function (Builder $query, array $data): void {
                $status = AssetStatus::tryFrom((string) ($data['value'] ?? ''));

                if ($status === null) {
                    return;
                }

                match ($status) {
                    AssetStatus::Unknown => $query->whereNull('expires_at'),
                    AssetStatus::Expired => $query->whereNotNull('expires_at')->whereDate('expires_at', '<', now()),
                    AssetStatus::Critical => $query->whereDate('expires_at', '>=', now())->whereDate('expires_at', '<=', now()->addDays(7)),
                    AssetStatus::Urgent => $query->whereDate('expires_at', '>', now()->addDays(7))->whereDate('expires_at', '<=', now()->addDays(14)),
                    AssetStatus::Upcoming => $query->whereDate('expires_at', '>', now()->addDays(14))->whereDate('expires_at', '<=', now()->addDays(30)),
                    AssetStatus::Ok => $query->whereDate('expires_at', '>', now()->addDays(30)),
                };
            })
            ->preload();
    }

    public static function ticketStatus(): SelectFilter
    {
        return SelectFilter::make('status')
            ->options(TicketStatus::options())
            ->label(__('admin.fields.status'))
            ->preload();
    }

    public static function ticketPriority(): SelectFilter
    {
        return SelectFilter::make('priority')
            ->options(TicketPriority::options())
            ->label(__('admin.tickets.priority'))
            ->preload();
    }

    public static function users(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('admin.fields.who'))
            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
            ->preload();
    }

    public static function admins(): SelectFilter
    {
        return SelectFilter::make('admin_user_id')
            ->label(__('admin.resources.admins.plural'))
            ->options(fn (): array => AdminUser::query()->orderBy('name')->pluck('name', 'id')->all())
            ->preload();
    }

    public static function activityAction(): SelectFilter
    {
        return SelectFilter::make('action')
            ->label(__('admin.fields.action'))
            ->options(fn (): array => DB::table('activity_logs')
                ->whereNotNull('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->mapWithKeys(fn ($action) => [
                    (string) $action => ActivityAction::label((string) $action),
                ])
                ->all())
            ->preload();
    }
}
