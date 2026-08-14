<?php

namespace App\Livewire\Concerns;

use App\Models\Workspace;

trait InteractsWithWorkspace
{
    protected function workspace(): Workspace
    {
        return auth()->user()->currentWorkspace;
    }
}
