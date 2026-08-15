<?php

namespace App\Livewire\Concerns;

use App\Models\Workspace;

trait InteractsWithWorkspace
{
    use ShowsToast;

    protected function workspace(): Workspace
    {
        return auth()->user()->currentWorkspace;
    }

    protected function assertOwner(): void
    {
        abort_unless(auth()->user()->isOwnerOf($this->workspace()), 403);
    }
}
