<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\Client;
use App\Models\User;

final class WorkspaceSearch
{
    public const MIN_LENGTH = 2;

    /**
     * @return list<array{key: string, label: string, items: list<array{title: string, subtitle: string, url: string}>}>
     */
    public function groups(User $user, string $q, int $limit = 5): array
    {
        $q = trim($q);
        $workspace = $user->currentWorkspace;

        if ($workspace === null || mb_strlen($q) < self::MIN_LENGTH) {
            return [];
        }

        $like = '%'.$q.'%';

        $clients = $workspace->clients()
            ->where(fn ($query) => $query->where('name', 'like', $like)
                ->orWhere('contact_name', 'like', $like)
                ->orWhere('email', 'like', $like))
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $assets = $workspace->assets()
            ->with(['client', 'assetType'])
            ->where(fn ($query) => $query->where('name', 'like', $like)
                ->orWhereHas('client', fn ($client) => $client->where('name', 'like', $like)))
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            [
                'key' => 'sections',
                'label' => __('app.nav.sections'),
                'items' => $this->sections($user, $q, $limit),
            ],
            [
                'key' => 'clients',
                'label' => __('app.nav.clients'),
                'items' => $clients->map(fn (Client $client): array => [
                    'title' => (string) $client->name,
                    'subtitle' => collect([$client->contact_name, $client->email])->filter()->implode(' · '),
                    'url' => route('clients.show', $client),
                ])->all(),
            ],
            [
                'key' => 'assets',
                'label' => __('app.nav.assets'),
                'items' => $assets->map(fn (Asset $asset): array => [
                    'title' => (string) $asset->name,
                    'subtitle' => collect([$asset->assetType?->displayLabel(), $asset->client?->name])->filter()->implode(' · '),
                    'url' => route('assets.show', $asset),
                ])->all(),
            ],
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string}>
     */
    private function sections(User $user, string $q, int $limit): array
    {
        return collect($this->availableSections($user))
            ->filter(fn (array $section): bool => mb_stripos($section['label'], $q) !== false)
            ->take($limit)
            ->map(fn (array $section): array => [
                'title' => $section['label'],
                'subtitle' => '',
                'url' => route($section['route']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{route: string, label: string}>
     */
    private function availableSections(User $user): array
    {
        $workspace = $user->currentWorkspace;
        $isOwner = $workspace !== null && $user->isOwnerOf($workspace);

        return collect([
            ['route' => 'dashboard', 'label' => __('app.nav.dashboard')],
            ['route' => 'clients', 'label' => __('app.nav.clients')],
            ['route' => 'assets', 'label' => __('app.nav.assets')],
            ['route' => 'reports', 'label' => __('app.nav.reports')],
            ['route' => 'notifications', 'label' => __('app.nav.notifications')],
            ['route' => 'activity', 'label' => __('app.nav.activity')],
            ['route' => 'support', 'label' => __('app.nav.support')],
            ['route' => 'settings.account', 'label' => __('app.nav.account')],
        ])
            ->when($isOwner, fn ($sections) => $sections->concat([
                ['route' => 'settings.team', 'label' => __('app.nav.team')],
                ['route' => 'settings.reminders', 'label' => __('app.nav.reminders')],
                ['route' => 'settings.types', 'label' => __('app.nav.types')],
                ['route' => 'import', 'label' => __('app.nav.import')],
                ['route' => 'export', 'label' => __('app.nav.export')],
            ]))
            ->when(
                $isOwner && (Edition::enabled('public_api') || Edition::enabled('webhooks')),
                fn ($sections) => $sections->push(['route' => 'settings.api', 'label' => __('app.nav.api')]),
            )
            ->when(
                $isOwner && Edition::enabled('billing'),
                fn ($sections) => $sections->push(['route' => 'settings.billing', 'label' => __('app.nav.billing')]),
            )
            ->values()
            ->all();
    }
}
