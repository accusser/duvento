<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Rules\HttpWebsite;
use App\Support\ActivityLogger;
use App\Support\PlanGuard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $contactName = '';

    public string $email = '';

    public string $website = '';

    public string $notes = '';

    public function mount(): void
    {
        $edit = (int) request('edit');

        if ($edit > 0) {
            $this->edit($edit);

            return;
        }

        if (request()->boolean('create')) {
            $this->create();
        }
    }

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
        $this->contactName = $client->contact_name ?? '';
        $this->email = $client->email ?? '';
        $this->website = $client->website ?? '';
        $this->notes = $client->notes ?? '';
        $this->showForm = true;
    }

    public function save(PlanGuard $guard)
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'contactName' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255', new HttpWebsite],
            'notes' => ['nullable', 'string'],
        ]);

        $workspace = $this->workspace();
        $payload = [
            'name' => $validated['name'],
            'contact_name' => $validated['contactName'] ?: null,
            'email' => $validated['email'] ?: null,
            'website' => $validated['website'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        if ($this->editingId) {
            $client = $workspace->clients()->findOrFail($this->editingId);
            $client->update($payload);
            ActivityLogger::log($workspace, 'client.updated', $client, ['name' => $client->name]);
        } else {
            $guard->assertCanCreateClient($workspace);
            $client = $workspace->clients()->create($payload);
            ActivityLogger::log($workspace, 'client.created', $client, ['name' => $client->name]);
            $this->flashToast(__('app.flash.client_added'));

            return $this->redirect(route('clients.show', $client), navigate: true);
        }

        $this->resetForm();
        $this->toast(__('app.flash.client_saved'));
    }

    public function delete(int $id): void
    {
        $this->assertOwner();
        $client = $this->workspace()->clients()->findOrFail($id);
        ActivityLogger::log($this->workspace(), 'client.deleted', $client, ['name' => $client->name]);
        $client->delete();
        $this->toast(__('app.flash.client_deleted'), 'delete');
    }

    #[On('close-modal')]
    public function close(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'contactName', 'email', 'website', 'notes']);
        $this->resetValidation();
    }

    public function render()
    {
        $clients = $this->workspace()->clients()
            ->withCount('assets')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('contact_name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('website', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->get();

        return view('livewire.clients.index', compact('clients'))->title(__('app.titles.clients'));
    }
}
