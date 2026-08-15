<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Asset;
use App\Support\ActivityLogger;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    use InteractsWithWorkspace;

    #[Locked]
    public int $assetId;

    public bool $showRenew = false;

    public string $renewDate = '';

    public function mount(int $asset): void
    {
        $this->assetId = $this->workspace()->assets()->findOrFail($asset)->id;
    }

    public function beginRenew(): void
    {
        $asset = $this->asset();
        $this->renewDate = ($asset->expires_at ?? now())->copy()->addYear()->toDateString();
        $this->showRenew = true;
    }

    public function confirmRenew(): void
    {
        $this->validate([
            'renewDate' => ['required', 'date'],
        ]);

        $asset = $this->asset();
        $from = $asset->expires_at?->toDateString();
        $asset->update(['expires_at' => $this->renewDate]);
        ActivityLogger::log($this->workspace(), 'asset.renewed', $asset, [
            'name' => $asset->name,
            'from' => $from,
            'to' => $this->renewDate,
        ]);
        $this->close();
        $this->toast(__('app.flash.renewed'));
    }

    public function delete()
    {
        $this->assertOwner();
        $asset = $this->asset();
        ActivityLogger::log($this->workspace(), 'asset.deleted', $asset, ['name' => $asset->name]);
        $asset->delete();
        $this->flashToast(__('app.flash.asset_deleted'), 'delete');

        return $this->redirect(route('assets'), navigate: true);
    }

    #[On('close-modal')]
    public function close(): void
    {
        $this->reset(['showRenew', 'renewDate']);
        $this->resetValidation();
    }

    public function render()
    {
        $asset = $this->asset()->load(['client', 'assetType', 'reminderRules', 'workspace']);
        $days = $asset->effectiveReminderRules()
            ->pluck('days_before')
            ->sortDesc()
            ->values();

        return view('livewire.assets.show', [
            'asset' => $asset,
            'reminderDays' => $days,
            'logs' => $this->workspace()->activityLogs()
                ->where('subject_type', $asset->getMorphClass())
                ->where('subject_id', $asset->id)
                ->with('user')
                ->latest()
                ->limit(20)
                ->get(),
        ])->title(__('app.titles.asset_show', ['name' => $asset->name]));
    }

    private function asset(): Asset
    {
        return $this->workspace()->assets()->findOrFail($this->assetId);
    }
}
