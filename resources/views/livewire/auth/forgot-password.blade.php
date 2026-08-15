<div class="auth-page">
    <div class="auth-card">
        <a href="{{ url('/') }}" class="auth-brand" wire:navigate>
            <span class="brand-mark">D</span>
            <span class="ny-display-h">{{ __('app.brand') }}</span>
        </a>
        <h4 class="text-center mb-1 fw-bold">{{ __('app.auth.reset_title') }}</h4>
        <p class="text-muted text-center mb-4">{{ __('app.auth.reset_sub') }}</p>
        @if ($sent)
            <div class="alert alert-success">{{ __('app.auth.reset_sent') }}</div>
        @endif
        <form wire:submit="send">
            <x-ui.input :label="__('app.fields.email')" type="email" wire:model="email" autocomplete="username" placeholder="you@example.com" />
            @error('email') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <button type="submit" class="btn btn-primary w-100 mb-3">{{ __('app.auth.send_link') }}</button>
            <p class="text-center small mb-0 text-muted">
                <a href="{{ route('login') }}" class="text-primary fw-semibold" wire:navigate>{{ __('app.auth.back_to_login') }}</a>
            </p>
        </form>
    </div>
</div>
