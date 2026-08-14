<?php

namespace App\Livewire\Settings;

use App\Enums\ReminderChannel;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\ReminderRule;
use App\Support\ActivityLogger;
use App\Support\Edition;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Напоминания — Duvento')]
class Reminders extends Component
{
    use InteractsWithWorkspace;

    public array $days = [];

    public function mount(): void
    {
        $this->days = $this->workspace()->reminderRules()
            ->whereNull('asset_id')
            ->where('channel', ReminderChannel::Email)
            ->orderByDesc('days_before')
            ->pluck('days_before')
            ->all();

        if ($this->days === []) {
            $this->days = [30, 14, 7, 1];
        }
    }

    public function addDay(): void
    {
        $this->days[] = 3;
    }

    public function removeDay(int $index): void
    {
        unset($this->days[$index]);
        $this->days = array_values($this->days);
    }

    public function save(): void
    {
        $days = collect($this->days)
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $this->validate([
            'days' => ['required', 'array', 'min:1'],
        ]);

        $workspace = $this->workspace();
        $workspace->reminderRules()->whereNull('asset_id')->delete();

        $days->each(fn (int $day) => ReminderRule::query()->create([
            'workspace_id' => $workspace->id,
            'asset_id' => null,
            'days_before' => $day,
            'channel' => ReminderChannel::Email,
        ]));

        $this->days = $days->all();
        ActivityLogger::log($workspace, 'reminders.updated', null, ['days' => $this->days]);
        session()->flash('status', 'Правила напоминаний сохранены.');
    }

    public function render()
    {
        return view('livewire.settings.reminders', [
            'telegramEnabled' => Edition::enabled('telegram'),
        ]);
    }
}
