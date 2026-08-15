<?php

namespace App\Livewire\Search;

use App\Support\WorkspaceSearch;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $q = '';

    public function mount(): void
    {
        $this->q = (string) request('search', '');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.search.global-search', [
            'groups' => $user === null ? [] : collect(app(WorkspaceSearch::class)->groups($user, $this->q))
                ->filter(fn (array $group): bool => $group['items'] !== [])
                ->values()
                ->all(),
        ]);
    }
}
