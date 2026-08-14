<?php

namespace App\Livewire\Activity;

use App\Livewire\Concerns\InteractsWithWorkspace;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Журнал — Duvento')]
class Index extends Component
{
    use InteractsWithWorkspace;
    use WithPagination;

    public function render()
    {
        $logs = $this->workspace()->activityLogs()
            ->with('user')
            ->latest()
            ->paginate(30);

        return view('livewire.activity.index', compact('logs'));
    }
}
