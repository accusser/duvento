@props(['open' => false, 'title' => ''])

@if ($open)
    <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" style="background: rgba(15, 18, 40, .45);" wire:click.self="$dispatch('close-modal')">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" aria-label="{{ __('app.common.close') }}" wire:click="$dispatch('close-modal')"></button>
                </div>
                <div class="modal-body">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
@endif
