<div class="auth-page">
    <div class="auth-card">
        <a href="{{ url('/') }}" class="auth-brand" wire:navigate>
            <span class="brand-mark">D</span>
            <span class="ny-display-h">{{ __('app.brand') }}</span>
        </a>
        <h4 class="text-center mb-1 fw-bold">{{ __('app.auth.create_account') }}</h4>
        @if (\App\Support\Edition::isCloud())
            <p class="text-muted text-center mb-4">{{ __('app.auth.register_cloud') }}</p>
        @else
            <p class="text-muted text-center mb-4">{{ __('app.auth.register_selfhost') }}</p>
        @endif
        <form wire:submit="register">
            <x-ui.input :label="__('app.fields.name')" wire:model="name" placeholder="{{ __('app.fields.name') }}" />
            @error('name') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.email')" type="email" wire:model="email" autocomplete="username" placeholder="you@company.com" :disabled="(bool) $inviteToken" />
            @error('email') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            @if ($inviteToken)
                <p class="small text-muted">{{ __('app.auth.register_invite') }}</p>
            @else
                <x-ui.input :label="__('app.fields.workspace')" wire:model="workspace" placeholder="{{ __('app.auth.workspace_placeholder') }}" />
                @error('workspace') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            @endif
            <x-ui.input :label="__('app.fields.password')" type="password" wire:model="password" autocomplete="new-password" placeholder="{{ __('app.auth.password_hint') }}" />
            @error('password') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
            <x-ui.input :label="__('app.fields.password_confirmation')" type="password" wire:model="password_confirmation" autocomplete="new-password" />
            <button type="submit" class="btn btn-primary w-100 mb-3">{{ __('app.auth.submit_register') }}</button>
            <p class="text-center small mb-0 text-muted">
                {{ __('app.auth.has_account') }} <a href="{{ route('login') }}" class="text-primary fw-semibold" wire:navigate>{{ __('app.auth.login') }}</a>
            </p>
        </form>
    </div>
</div>
