<div>
    <x-page-head :title="__('app.types.title')" :sub="__('app.types.sub')" />

    <h6 class="text-muted mb-2">{{ __('app.types.system') }}</h6>
    <div class="card mb-4">
        <div class="list-group list-group-flush">
            @foreach ($system as $type)
                <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                    <span class="min-w-0">{{ $type->displayLabel() }}</span>
                    <code class="small text-muted flex-shrink-0">{{ $type->key }}</code>
                </div>
            @endforeach
        </div>
    </div>

    <h6 class="text-muted mb-2">{{ __('app.types.custom') }}</h6>
    <form wire:submit="add" class="ny-inline-add">
        <x-ui.input group-class="flex-grow-1 mb-0" :label="__('app.fields.label')" wire:model="label" placeholder="{{ __('app.types.placeholder') }}" />
        <x-ui.button variant="accent" type="submit">{{ __('app.common.add') }}</x-ui.button>
    </form>
    @error('label') <div class="invalid-feedback d-block mb-3">{{ $message }}</div> @enderror

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($custom as $type)
                <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                    <span class="min-w-0 text-truncate">{{ $type->label }}</span>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <code class="small text-muted">{{ $type->key }}</code>
                        <x-ui.button variant="danger" icon="trash-can-outline" :tip="__('app.common.delete')" wire:click="delete({{ $type->id }})" wire:confirm="{{ __('app.types.confirm_delete') }}" />
                    </div>
                </div>
            @empty
                <div class="ny-list-empty">{{ __('app.types.empty') }}</div>
            @endforelse
        </div>
    </div>
</div>
