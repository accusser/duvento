@props(['open' => false, 'title' => ''])

@if ($open)
    <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:items-center" wire:click.self="$dispatch('close-modal')">
        <div class="w-full max-w-lg rounded-[10px] border border-border bg-card p-6" role="dialog" aria-modal="true">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="font-display text-lg font-semibold">{{ $title }}</h2>
                <button type="button" class="text-sm text-muted hover:text-ink" wire:click="$dispatch('close-modal')">Закрыть</button>
            </div>
            {{ $slot }}
        </div>
    </div>
@endif
