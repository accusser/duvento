<?php

namespace App\Livewire\Admin;

use App\Support\AdminSearch;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $q = '';

    public function mount(): void
    {
        $this->q = (string) request('q', '');
    }

    public function render()
    {
        return view('livewire.admin.global-search', [
            'groups' => collect(app(AdminSearch::class)->groups($this->q, 5))
                ->filter(fn (array $group): bool => $group['items'] !== [])
                ->values()
                ->all(),
        ]);
    }
}
