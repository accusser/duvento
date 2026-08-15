<?php

namespace App\Filament\Pages;

use App\Support\AdminSearch as Search;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class AdminSearch extends Page
{
    protected string $view = 'filament.pages.admin-search';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'search';

    #[Url]
    public string $q = '';

    public static function getNavigationLabel(): string
    {
        return __('admin.search.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.search.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('admin.search.description');
    }

    protected function getViewData(): array
    {
        return [
            'groups' => collect(app(Search::class)->groups($this->q))
                ->filter(fn (array $group): bool => $group['items'] !== [])
                ->values()
                ->all(),
        ];
    }
}
