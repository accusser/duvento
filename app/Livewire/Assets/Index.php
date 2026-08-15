<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\AssetType;
use App\Support\ActivityLogger;
use App\Support\AssetQuery;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use InteractsWithWorkspace;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $clientId = '';

    #[Url]
    public string $typeId = '';

    #[Url(as: 'owner')]
    public string $ownerFilter = '';

    #[Url(as: 'payer')]
    public string $payerFilter = '';

    #[Url]
    public string $expiry = '';

    public bool $showRenew = false;

    public ?int $renewingId = null;

    public string $renewingName = '';

    public string $renewDate = '';

    public function beginRenew(int $id): void
    {
        $asset = $this->workspace()->assets()->findOrFail($id);
        $this->renewingId = $asset->id;
        $this->renewingName = $asset->name;
        $this->renewDate = ($asset->expires_at ?? now())->copy()->addYear()->toDateString();
        $this->showRenew = true;
    }

    public function confirmRenew(): void
    {
        $this->validate([
            'renewingId' => ['required', 'integer'],
            'renewDate' => ['required', 'date'],
        ]);

        $this->markRenewed((int) $this->renewingId, $this->renewDate);
        $this->close();
        $this->toast(__('app.flash.renewed'));
    }

    public function markRenewed(int $id, string $date): void
    {
        $asset = $this->workspace()->assets()->findOrFail($id);
        $from = $asset->expires_at?->toDateString();
        $asset->update(['expires_at' => $date]);
        ActivityLogger::log($this->workspace(), 'asset.renewed', $asset, [
            'name' => $asset->name,
            'from' => $from,
            'to' => $date,
        ]);
    }

    public function delete(int $id): void
    {
        $this->assertOwner();
        $asset = $this->workspace()->assets()->findOrFail($id);
        ActivityLogger::log($this->workspace(), 'asset.deleted', $asset, ['name' => $asset->name]);
        $asset->delete();
        $this->toast(__('app.flash.asset_deleted'), 'delete');
    }

    #[On('close-modal')]
    public function close(): void
    {
        $this->reset(['showRenew', 'renewingId', 'renewingName', 'renewDate']);
        $this->resetValidation();
    }

    public function render()
    {
        $workspace = $this->workspace();
        $types = AssetType::query()->availableFor($workspace)->get()
            ->sortBy(fn (AssetType $type) => mb_strtolower($type->displayLabel()));

        return view('livewire.assets.index', [
            'assets' => AssetQuery::filtered(
                $workspace,
                $this->search,
                $this->status ?: null,
                $this->clientId !== '' ? (int) $this->clientId : null,
                [
                    'typeId' => $this->typeId !== '' ? (int) $this->typeId : null,
                    'owner' => $this->ownerFilter ?: null,
                    'payer' => $this->payerFilter ?: null,
                    'expiry' => $this->expiry ?: null,
                ],
            ),
            'clients' => $workspace->clients()->orderBy('name')->get(),
            'systemTypes' => $types->filter->isSystem()->values(),
            'customTypes' => $types->reject->isSystem()->values(),
        ])->title(__('app.titles.assets'));
    }
}
