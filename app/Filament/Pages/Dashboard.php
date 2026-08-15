<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.nav.dashboard');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('admin.nav.dashboard_description');
    }
}
