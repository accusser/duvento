<?php

namespace App\Support;

use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Workspaces\WorkspaceResource;
use App\Models\Asset;
use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class AdminSearch
{
    public const MIN_LENGTH = 2;

    /**
     * @return list<array{key: string, label: string, items: list<array{title: string, subtitle: string, url: string}>}>
     */
    public function groups(string $q, int $limit = 10): array
    {
        $q = trim($q);

        if (mb_strlen($q) < self::MIN_LENGTH) {
            return [];
        }

        $like = '%'.$q.'%';

        return [
            $this->sections($q, $limit),
            $this->group(
                'workspaces',
                WorkspaceResource::class,
                'edit',
                Workspace::query()->where('name', 'like', $like)->orderBy('name')->limit($limit)->get(),
                fn (Workspace $workspace): array => [$workspace->plan?->label()],
            ),
            $this->group(
                'users',
                UserResource::class,
                'view',
                User::query()
                    ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orderBy('name')
                    ->limit($limit)
                    ->get(),
                fn (User $user): array => [$user->email],
            ),
            $this->group(
                'clients',
                ClientResource::class,
                'view',
                Client::query()
                    ->with('workspace')
                    ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orderBy('name')
                    ->limit($limit)
                    ->get(),
                fn (Client $client): array => [$client->email, $client->workspace?->name],
            ),
            $this->group(
                'assets',
                AssetResource::class,
                'view',
                Asset::query()
                    ->with(['workspace', 'client'])
                    ->where('name', 'like', $like)
                    ->orderBy('name')
                    ->limit($limit)
                    ->get(),
                fn (Asset $asset): array => [$asset->client?->name, $asset->workspace?->name],
            ),
        ];
    }

    /**
     * @return array{key: string, label: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function sections(string $q, int $limit): array
    {
        $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();

        $items = collect($panel->getNavigation())
            ->flatMap(fn (NavigationGroup $group) => $group->getItems())
            ->filter(fn (NavigationItem $item): bool => mb_stripos((string) $item->getLabel(), $q) !== false)
            ->take($limit)
            ->map(fn (NavigationItem $item): array => [
                'title' => (string) $item->getLabel(),
                'subtitle' => '',
                'url' => (string) $item->getUrl(),
            ])
            ->values()
            ->all();

        return [
            'key' => 'sections',
            'label' => __('admin.header.sections'),
            'items' => $items,
        ];
    }

    /**
     * @param  Collection<int, Model>  $records
     * @param  callable(Model): list<?string>  $subtitle
     * @return array{key: string, label: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function group(string $key, string $resource, string $page, Collection $records, callable $subtitle): array
    {
        return [
            'key' => $key,
            'label' => __('admin.resources.'.$key.'.plural'),
            'items' => $records->map(fn (Model $record): array => [
                'title' => (string) $record->name,
                'subtitle' => collect($subtitle($record))->filter()->implode(' · '),
                'url' => $resource::getUrl($page, ['record' => $record]),
            ])->all(),
        ];
    }
}
