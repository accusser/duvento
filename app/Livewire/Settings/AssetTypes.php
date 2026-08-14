<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\AssetType;
use App\Support\ActivityLogger;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Типы активов — Duvento')]
class AssetTypes extends Component
{
    use InteractsWithWorkspace;

    public string $label = '';

    public function add(): void
    {
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
    }

    public function delete(int $id): void
    {
        $type = $this->workspace()->assetTypes()->findOrFail($id);

        if ($type->assets()->exists()) {
            $this->addError('label', 'Нельзя удалить тип, пока к нему привязаны активы.');

            return;
        }

        ActivityLogger::log($this->workspace(), 'asset_type.deleted', $type, ['label' => $type->label]);
        $type->delete();
    }

    public function render()
    {
        $workspace = $this->workspace();

        return view('livewire.settings.asset-types', [
            'system' => AssetType::query()->whereNull('workspace_id')->orderBy('label')->get(),
            'custom' => $workspace->assetTypes()->orderBy('label')->get(),
        ]);
    }
}
