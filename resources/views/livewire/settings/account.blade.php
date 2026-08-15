<div>
    <x-page-head :title="__('app.account.title')" :sub="__('app.account.sub')" />

    <div class="card mb-3">
        <div class="card-body">
            <form wire:submit="save">
                <x-ui.input :label="__('app.fields.name')" wire:model="name" />
                @error('name') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                <x-ui.input :label="__('app.fields.email')" type="email" wire:model="email" />
                @error('email') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror

                <h6 class="mt-2 mb-3">{{ __('app.account.change_password') }}</h6>
                <x-ui.input :label="__('app.fields.current_password')" type="password" wire:model="currentPassword" />
                @error('currentPassword') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                <x-ui.input :label="__('app.fields.new_password')" type="password" wire:model="password" />
                @error('password') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                <x-ui.input :label="__('app.account.password_again')" type="password" wire:model="passwordConfirmation" />
                <div class="d-flex flex-wrap align-items-center gap-2 mt-n2 mb-3">
                    <small class="text-muted">{{ __('app.auth.password_hint') }}</small>
                    <x-ui.button variant="soft" wire:click="generatePassword">
                        <i class="mdi mdi-auto-fix me-1"></i>{{ __('app.common.generate_password') }}
                    </x-ui.button>
                </div>

                <x-ui.button variant="accent" type="submit">{{ __('app.common.save') }}</x-ui.button>
            </form>
        </div>
    </div>

    @if ($isOwner)
        <div class="card mb-3">
            <div class="card-body">
                <form wire:submit="saveWorkspace">
                    <h5 class="mb-2">{{ __('app.account.workspace_title') }}</h5>
                    <p class="small text-muted">{{ __('app.account.workspace_currency_hint') }}</p>
                    <x-ui.select :label="__('app.fields.currency')" wire:model="workspaceCurrency">
                        @foreach ($currencies as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </x-ui.select>
                    @error('workspaceCurrency') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                    <x-ui.button variant="accent" type="submit">{{ __('app.common.save') }}</x-ui.button>
                </form>
            </div>
        </div>
    @endif

    <div class="card mb-3 {{ $user->hasVerifiedEmail() ? '' : 'border-warning' }}">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="fw-semibold d-flex align-items-center gap-2">
                    <i class="mdi {{ $user->hasVerifiedEmail() ? 'mdi-email-check-outline' : 'mdi-email-alert-outline' }}"></i>
                    {{ __('app.account.verify_title') }}
                </div>
                <p class="small text-muted mb-0 mt-2">
                    @if ($user->hasVerifiedEmail())
                        {{ __('app.account.verify_on', ['date' => $user->email_verified_at->translatedFormat('d M Y')]) }}
                    @else
                        {{ __('app.account.verify_needed') }}
                    @endif
                </p>
            </div>
            @if ($user->hasVerifiedEmail())
                <span class="badge badge-soft-success">
                    <i class="mdi mdi-check-circle-outline me-1"></i>{{ __('app.account.verified') }}
                </span>
            @else
                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge badge-soft-warning">
                        <i class="mdi mdi-alert-outline me-1"></i>{{ __('app.account.unverified') }}
                    </span>
                    <x-ui.button variant="accent" wire:click="sendVerification">{{ __('app.account.verify_send') }}</x-ui.button>
                    @error('verification') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">{{ __('app.account.smtp_title') }}</h5>
            <p class="text-muted small">{{ __('app.account.smtp_hint', ['mail' => 'MAIL_*']) }}</p>
            <x-ui.button wire:click="sendTestMail">{{ __('app.account.smtp_send') }}</x-ui.button>
            @error('smtp') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="card border-danger-subtle">
        <div class="card-body">
            <form wire:submit="deleteAccount">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="fw-semibold text-danger d-flex align-items-center gap-2">
                            <i class="mdi mdi-delete-outline"></i>
                            {{ __('app.account.delete_title') }}
                        </div>
                        <p class="small text-muted mb-0 mt-2">
                            {{ auth()->user()->ownsCurrentWorkspace() ? __('app.account.delete_hint_owner') : __('app.account.delete_hint_member') }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <x-ui.input :label="__('app.fields.current_password')" type="password" wire:model="deletePassword" autocomplete="current-password" />
                        @error('deletePassword') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                        <x-ui.button variant="danger" type="submit" wire:confirm="{{ __('app.account.confirm_delete') }}">{{ __('app.account.delete_action') }}</x-ui.button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
