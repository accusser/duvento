<?php

namespace App\Events;

use App\Models\ActivityLog;
use Illuminate\Foundation\Events\Dispatchable;

class WorkspaceActivityOccurred
{
    use Dispatchable;

    public function __construct(public ActivityLog $log) {}
}
