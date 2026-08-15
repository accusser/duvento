<div class="auth-page">
    <div class="auth-card">
        <a href="{{ url('/') }}" class="auth-brand" wire:navigate>
            <span class="brand-mark">D</span>
            <span class="ny-display-h">{{ __('app.brand') }}</span>
        </a>
        <h4 class="text-center mb-1 fw-bold">{{ __('app.auth.welcome_back') }}</h4>
        <p class="text-muted text-center mb-4">{{ __('app.auth.login_sub') }}</p>
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <form wire:submit="authenticate">
            <x-ui.input :label="__('app.fields.email')" type="email" wire:model="email" autocomplete="username" placeholder="you@example.com" />
            @error('email') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.password')" type="password" wire:model="password" autocomplete="current-password" placeholder="{{ __('app.fields.password') }}" />
            @error('password') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" wire:model="remember" id="remember">
                    <label class="form-check-label small" for="remember">{{ __('app.auth.remember') }}</label>
                </div>
                <a href="{{ route('password.request') }}" class="small" wire:navigate>{{ __('app.auth.forgot') }}</a>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="mdi mdi-login me-1"></i>{{ __('app.auth.login') }}
            </button>
            <p class="text-center small mb-0 text-muted">
                {{ __('app.auth.no_account') }} <a href="{{ route('register') }}" class="text-primary fw-semibold" wire:navigate>{{ __('app.auth.register') }}</a>
            </p>
        </form>
    </div>
</div>
