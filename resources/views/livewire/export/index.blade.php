<div>
    <x-page-head :title="__('app.export.title')" :sub="__('app.export.sub')" />

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3 mb-4">
                <span class="stat-icon-wrap mint">
                    <i class="mdi mdi-database-export-outline"></i>
                </span>
                <div>
                    <h5 class="mb-1">{{ __('app.export.card_title') }}</h5>
                    <p class="text-muted small mb-0">{{ __('app.export.card_sub') }}</p>
                </div>
            </div>

            <form wire:submit="download">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8 col-xl-5">
                        <label for="export-dataset" class="form-label fw-semibold">
                            {{ __('app.export.choose') }}
                        </label>
                        <select id="export-dataset" class="form-select" wire:model="dataset">
                            <option value="clients">{{ __('app.export.clients') }}</option>
                            <option value="assets">{{ __('app.export.assets') }}</option>
                            <option value="activity">{{ __('app.export.activity') }}</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <x-ui.button variant="accent" type="submit" wire:loading.attr="disabled">
                            <i class="mdi mdi-download me-1"></i>
                            <span wire:loading.remove>{{ __('app.export.download') }}</span>
                            <span wire:loading>{{ __('app.export.preparing') }}</span>
                        </x-ui.button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
