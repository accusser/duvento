<?php

namespace App\Livewire\Export;

use App\Livewire\Concerns\InteractsWithWorkspace;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;

    public string $dataset = 'clients';

    public function mount(): void
    {
        $this->assertOwner();
    }

    public function download(): void
    {
        $this->assertOwner();
        $this->validate([
            'dataset' => ['required', 'in:clients,assets,activity'],
        ]);

        $this->redirectRoute('export.'.$this->dataset, navigate: false);
    }

    public function render()
    {
        return view('livewire.export.index')->title(__('app.titles.export'));
    }
}
