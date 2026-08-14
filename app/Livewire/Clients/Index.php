<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\ActivityLogger;
use App\Support\PlanGuard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Клиенты — Duvento')]
class Index extends Component
{
    use InteractsWithWorkspace;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $notes = '';

    public function updatedSearch(): void
    {
        //
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $client = $this->workspace()->clients()->findOrFail($id);
        $this->editingId = $client->id;
        $this->name = $client->name;
        $this->email = $client->email ?? '';
        $this->notes = $client->notes ?? '';
        $this->showForm = true;
    }

    public function save(PlanGuard $guard): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $workspace = $this->workspace();

        if ($this->editingId) {
            $client = $workspace->clients()->findOrFail($this->editingId);
            $client->update($validated);
            ActivityLogger::log($workspace, 'client.updated', $client, ['name' => $client->name]);
        } else {
            $guard->assertCanCreateClient($workspace);
            $client = $workspace->clients()->create($validated);
            ActivityLogger::log($workspace, 'client.created', $client, ['name' => $client->name]);
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $client = $this->workspace()->clients()->findOrFail($id);
        ActivityLogger::log($this->workspace(), 'client.deleted', $client, ['name' => $client->name]);
        $client->delete();
    }

    #[On('close-modal')]
    public function close(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'email', 'notes']);
        $this->resetValidation();
    }

    public function render()
    {
        $clients = $this->workspace()->clients()
            ->withCount('assets')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->get();

        return view('livewire.clients.index', compact('clients'));
    }
}
