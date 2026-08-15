<?php

namespace App\Livewire\Assets;

use App\Enums\ReminderChannel;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\AssetType;
use App\Support\ActivityLogger;
use App\Support\PlanGuard;
use App\Support\UpcomingPayments;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    use InteractsWithWorkspace;

    #[Locked]
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

    public string $renewalCost = '';

    public string $currency = 'USD';

    public string $overrideDays = '';

    public function mount(?int $asset = null): void
    {
        if ($asset) {
            $model = $this->workspace()->assets()->findOrFail($asset);
            $this->editingId = $model->id;
            $this->formClientId = $model->client_id;
            $this->assetTypeId = $model->asset_type_id;
            $this->name = $model->name;
            $this->expiresAt = $model->expires_at?->toDateString() ?? '';
            $this->autoRenew = $model->auto_renew->value;
            $this->owner = $model->owner->value;
            $this->payer = $model->payer->value;
            $this->noticeEmail = $model->notice_email ?? '';
            $this->notes = $model->notes ?? '';
            $this->sslCheckEnabled = $model->ssl_check_enabled;
            $this->renewalCost = $model->renewal_cost === null ? '' : (string) $model->renewal_cost;
            $this->currency = UpcomingPayments::normalizeCurrency($model->currency ?: $this->workspace()->currencyCode());
            $this->overrideDays = $model->reminderRules()->pluck('days_before')->implode(',');

            return;
        }

        $clientId = (int) request('client_id');

        if ($clientId > 0 && $this->workspace()->clients()->whereKey($clientId)->exists()) {
            $this->formClientId = $clientId;
        }

        if (request()->boolean('ssl')) {
            $this->sslCheckEnabled = true;
            $this->assetTypeId = AssetType::query()->where('key', 'ssl')->whereNull('workspace_id')->value('id');
        }

        $this->currency = $this->workspace()->currencyCode();
    }

    public function save(PlanGuard $guard)
    {
        $workspace = $this->workspace();
        $this->currency = UpcomingPayments::normalizeCurrency($this->currency);
        $rules = [
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
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ];

        if (trim($this->renewalCost) !== '') {
            $rules['renewalCost'] = ['numeric', 'min:0'];
        }

        $validated = $this->validate($rules);
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
            'renewal_cost' => trim($this->renewalCost) === '' ? null : $this->renewalCost,
            'currency' => $this->currency,
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
        $this->flashToast($this->editingId ? __('app.flash.asset_saved') : __('app.flash.asset_added'));

        return $this->redirect(route('assets.show', $asset), navigate: true);
    }

    public function render()
    {
        $workspace = $this->workspace();
        $types = AssetType::query()->availableFor($workspace)->get()
            ->sortBy(fn (AssetType $type) => mb_strtolower($type->displayLabel()));

        return view('livewire.assets.form', [
            'clients' => $workspace->clients()->orderBy('name')->get(),
            'systemTypes' => $types->filter->isSystem()->values(),
            'customTypes' => $types->reject->isSystem()->values(),
            'workspaceDays' => $workspace->reminderRules()
                ->whereNull('asset_id')
                ->orderByDesc('days_before')
                ->pluck('days_before'),
            'backUrl' => $this->editingId
                ? route('assets.show', $this->editingId)
                : ($this->formClientId ? route('clients.show', $this->formClientId) : route('assets')),
            'backLabel' => $this->editingId
                ? __('app.assets.back_asset')
                : ($this->formClientId ? __('app.assets.back_client') : __('app.assets.back')),
        ])->title($this->editingId ? __('app.titles.asset_edit') : __('app.titles.asset_create'));
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
            'channel' => ReminderChannel::Email,
        ]));
    }
}
