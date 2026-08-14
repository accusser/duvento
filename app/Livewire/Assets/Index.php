<?php

namespace App\Livewire\Assets;

use App\Enums\AssetOwner;
use App\Enums\AssetPayer;
use App\Enums\AutoRenew;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Asset;
use App\Models\AssetType;
use App\Support\ActivityLogger;
use App\Support\AssetQuery;
use App\Support\PlanGuard;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Активы — Duvento')]
class Index extends Component
{
    use InteractsWithWorkspace;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $clientId = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $formClientId = null;

    public ?int $assetTypeId = null;

    public string $name = '';

    public string $expiresAt = '';

    public string $autoRenew = 'unknown';

    public string $owner = 'unknown';

    public string $payer = 'unknown';

    public string $noticeEmail = '';

    public string $notes = '';

    public bool $sslCheckEnabled = false;

    public string $overrideDays = '';

    public function create(): void
    {
        $this->resetForm();
        $this->formClientId = $this->clientId !== '' ? (int) $this->clientId : null;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $asset = $this->workspace()->assets()->findOrFail($id);
        $this->editingId = $asset->id;
        $this->formClientId = $asset->client_id;
        $this->assetTypeId = $asset->asset_type_id;
        $this->name = $asset->name;
        $this->expiresAt = $asset->expires_at?->toDateString() ?? '';
        $this->autoRenew = $asset->auto_renew->value;
        $this->owner = $asset->owner->value;
        $this->payer = $asset->payer->value;
        $this->noticeEmail = $asset->notice_email ?? '';
        $this->notes = $asset->notes ?? '';
        $this->sslCheckEnabled = $asset->ssl_check_enabled;
        $this->overrideDays = $asset->reminderRules()->pluck('days_before')->implode(',');
        $this->showForm = true;
    }

    public function save(PlanGuard $guard): void
    {
        $workspace = $this->workspace();
        $validated = $this->validate([
            'formClientId' => ['required', Rule::exists('clients', 'id')->where('workspace_id', $workspace->id)],
            'assetTypeId' => ['required', Rule::exists('asset_types', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'expiresAt' => ['nullable', 'date'],
            'autoRenew' => ['required', 'in:yes,no,unknown'],
            'owner' => ['required', 'in:agency,client,unknown'],
            'payer' => ['required', 'in:agency,client,unknown'],
            'noticeEmail' => ['nullable', 'email'],
            'notes' => ['nullable', 'string'],
            'sslCheckEnabled' => ['boolean'],
        ]);
        $payload = [
            'client_id' => $validated['formClientId'],
            'asset_type_id' => $validated['assetTypeId'],
            'name' => $validated['name'],
            'expires_at' => $validated['expiresAt'] ?: null,
            'auto_renew' => $validated['autoRenew'],
            'owner' => $validated['owner'],
            'payer' => $validated['payer'],
            'notice_email' => $validated['noticeEmail'] ?: null,
            'notes' => $validated['notes'] ?: null,
            'ssl_check_enabled' => $validated['sslCheckEnabled'],
        ];

        if ($this->editingId) {
            $asset = $workspace->assets()->findOrFail($this->editingId);
            $previous = $asset->expires_at?->toDateString();
            $asset->update($payload);
            $action = $previous !== $asset->expires_at?->toDateString() ? 'asset.date_changed' : 'asset.updated';
            ActivityLogger::log($workspace, $action, $asset, [
                'name' => $asset->name,
                'from' => $previous,
                'to' => $asset->expires_at?->toDateString(),
            ]);
        } else {
            $guard->assertCanCreateAsset($workspace);
            $asset = $workspace->assets()->create($payload);
            ActivityLogger::log($workspace, 'asset.created', $asset, ['name' => $asset->name]);
        }

        $this->syncOverrideRules($asset);
        $this->resetForm();
    }

    public function markRenewedFromForm(): void
    {
        if (! $this->editingId || $this->expiresAt === '') {
            $this->addError('expiresAt', 'Укажите новую дату истечения.');

            return;
        }

        $this->markRenewed($this->editingId, $this->expiresAt);
        $this->resetForm();
    }

    private function syncOverrideRules($asset): void
    {
        $days = collect(explode(',', $this->overrideDays))
            ->map(fn ($d) => (int) trim($d))
            ->filter(fn ($d) => $d > 0)
            ->unique();

        $asset->reminderRules()->delete();

        $days->each(fn (int $day) => $asset->reminderRules()->create([
            'workspace_id' => $asset->workspace_id,
            'days_before' => $day,
            'channel' => \App\Enums\ReminderChannel::Email,
        ]));
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
        $asset = $this->workspace()->assets()->findOrFail($id);
        ActivityLogger::log($this->workspace(), 'asset.deleted', $asset, ['name' => $asset->name]);
        $asset->delete();
    }

    #[On('close-modal')]
    public function close(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm', 'editingId', 'formClientId', 'assetTypeId', 'name', 'expiresAt',
            'autoRenew', 'owner', 'payer', 'noticeEmail', 'notes', 'sslCheckEnabled', 'overrideDays',
        ]);
        $this->autoRenew = AutoRenew::Unknown->value;
        $this->owner = AssetOwner::Unknown->value;
        $this->payer = AssetPayer::Unknown->value;
        $this->resetValidation();
    }

    public function render()
    {
        $workspace = $this->workspace();

        return view('livewire.assets.index', [
            'assets' => AssetQuery::filtered(
                $workspace,
                $this->search,
                $this->status ?: null,
                $this->clientId !== '' ? (int) $this->clientId : null,
            ),
            'clients' => $workspace->clients()->orderBy('name')->get(),
            'types' => AssetType::query()->availableFor($workspace)->orderBy('label')->get(),
        ]);
    }
}
