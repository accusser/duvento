<?php

namespace App\Livewire\Import;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\CsvImporter;
use App\Support\PlanGuard;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;
    use WithFileUploads;

    public string $target = 'clients';

    public $file = null;

    public function mount(): void
    {
        $this->assertOwner();
    }

    public function import(CsvImporter $importer, PlanGuard $guard): void
    {
        $this->assertOwner();
        $this->validate([
            'target' => ['required', 'in:clients,assets'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $count = $this->target === 'assets'
            ? $importer->importAssets($this->workspace(), $this->file, $guard)
            : $importer->importClients($this->workspace(), $this->file, $guard);

        $this->reset('file');
        $this->toast(__('app.flash.imported', ['count' => $count]));
    }

    public function render()
    {
        return view('livewire.import.index')->title(__('app.titles.import'));
    }
}
