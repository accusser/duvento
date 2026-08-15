<?php

namespace App\Livewire\Activity;

use App\Livewire\Concerns\InteractsWithWorkspace;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public function clear(): void
    {
        $this->assertOwner();
        $this->workspace()->activityLogs()->delete();
        $this->toast(__('app.flash.activity_cleared'), 'delete');
    }

    public function render()
    {
        $logs = $this->workspace()->activityLogs()
            ->with('user')
            ->latest()
            ->paginate(30);

        return view('livewire.activity.index', compact('logs'))->title(__('app.titles.activity'));
    }
}
