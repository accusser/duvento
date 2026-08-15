<?php

namespace App\Livewire\Concerns;

trait ShowsToast
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }

    protected function flashToast(string $message, string $type = 'success'): void
    {
        session()->flash('status', $message);
        session()->flash('status_type', $type);
    }
}
