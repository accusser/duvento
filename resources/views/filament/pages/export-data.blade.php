<x-filament-panels::page>
    <div class="card admin-export-card">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3 mb-4">
                <span class="admin-export-icon">
                    <i class="mdi mdi-database-export-outline"></i>
                </span>
                <div>
                    <h5 class="mb-1">{{ __('admin.export.card_title') }}</h5>
                    <p class="text-muted small mb-0">{{ __('admin.export.card_description') }}</p>
                </div>
            </div>

            <form wire:submit="export">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8 col-xl-6">
                        <label for="export-dataset" class="form-label fw-semibold">
                            {{ __('admin.export.choose') }}
                        </label>
                        <select id="export-dataset" class="form-select" wire:model="dataset">
                            @foreach ($this->datasets() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <i class="mdi mdi-download me-1"></i>
                            <span wire:loading.remove>{{ __('admin.export.download') }}</span>
                            <span wire:loading>{{ __('admin.export.preparing') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
