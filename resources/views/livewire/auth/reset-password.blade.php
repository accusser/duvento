<div class="auth-page">
    <div class="auth-card">
        <a href="{{ url('/') }}" class="auth-brand" wire:navigate>
            <span class="brand-mark">D</span>
            <span class="ny-display-h">{{ __('app.brand') }}</span>
        </a>
        <h4 class="text-center mb-1 fw-bold">{{ __('app.auth.new_password') }}</h4>
        <p class="text-muted text-center mb-4">{{ __('app.auth.new_password_sub') }}</p>
        <form wire:submit="resetPassword">
            <x-ui.input :label="__('app.fields.email')" type="email" wire:model="email" autocomplete="username" />
            @error('email') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.new_password')" type="password" wire:model="password" autocomplete="new-password" placeholder="{{ __('app.auth.password_hint') }}" />
            @error('password') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.password_confirmation')" type="password" wire:model="password_confirmation" autocomplete="new-password" />
            <button type="submit" class="btn btn-primary w-100 mb-3">{{ __('app.auth.save_password') }}</button>
        </form>
    </div>
</div>
