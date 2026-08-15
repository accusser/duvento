<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Widgets\Widget;

class LatestUsers extends Widget
{
    protected string $view = 'filament.widgets.latest-users';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected static ?int $sort = 10;

    protected function getViewData(): array
    {
        return [
            'users' => User::query()->with('workspaces')->latest()->limit(8)->get(),
            'viewUrl' => fn (User $user): string => UserResource::getUrl('view', ['record' => $user]),
        ];
    }
}
