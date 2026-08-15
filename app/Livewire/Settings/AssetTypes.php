<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\AssetType;
use App\Support\ActivityLogger;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AssetTypes extends Component
{
    use InteractsWithWorkspace;

    public string $label = '';

    public function mount(): void
    {
        $this->assertOwner();
    }

    public function add(): void
    {
        $this->assertOwner();
        $validated = $this->validate([
            'label' => ['required', 'string', 'max:80'],
        ]);

        $workspace = $this->workspace();
        $key = Str::slug($validated['label']) ?: 'custom';

        $type = AssetType::query()->create([
            'workspace_id' => $workspace->id,
            'key' => $key.'-'.substr(Str::lower(Str::random(4)), 0, 4),
            'label' => $validated['label'],
            'icon' => 'dot',
            'default_reminder_days' => [30, 14, 7, 1],
        ]);

        ActivityLogger::log($workspace, 'asset_type.created', $type, ['label' => $type->label]);
        $this->reset('label');
        $this->toast(__('app.flash.type_added'));
    }

    public function delete(int $id): void
    {
        $this->assertOwner();
        $type = $this->workspace()->assetTypes()->findOrFail($id);

        if ($type->assets()->exists()) {
            $this->addError('label', __('app.types.in_use'));

            return;
        }

        ActivityLogger::log($this->workspace(), 'asset_type.deleted', $type, ['label' => $type->label]);
        $type->delete();
        $this->toast(__('app.flash.type_deleted'), 'delete');
    }

    public function render()
    {
        $workspace = $this->workspace();

        return view('livewire.settings.asset-types', [
            'system' => AssetType::query()->whereNull('workspace_id')->get()
                ->sortBy(fn (AssetType $type) => mb_strtolower($type->displayLabel())),
            'custom' => $workspace->assetTypes()->orderBy('label')->get(),
        ])->title(__('app.titles.types'));
    }
}
