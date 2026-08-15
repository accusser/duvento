<div>
    @php($isOwner = auth()->user()->ownsCurrentWorkspace())

    <a href="{{ $backUrl }}" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1 mb-3" wire:navigate>
        <i class="mdi mdi-arrow-left"></i> {{ $backLabel }}
    </a>

    <x-page-head
        :title="$editingId ? __('app.assets.edit_page') : __('app.assets.new')"
        :sub="__('app.assets.form_sub')"
    />

    <div class="card">
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.select :label="__('app.fields.client')" wire:model="formClientId">
                            <option value="">{{ __('app.common.select') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('formClientId') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <x-ui.select :label="__('app.fields.type')" wire:model="assetTypeId">
                            <option value="">{{ __('app.common.select') }}</option>
                            <optgroup label="{{ __('app.assets.system_types') }}">
                                @foreach ($systemTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->displayLabel() }}</option>
                                @endforeach
                            </optgroup>
                            @if ($customTypes->isNotEmpty())
                                <optgroup label="{{ __('app.assets.custom_types') }}">
                                    @foreach ($customTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->displayLabel() }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </x-ui.select>
                        <p class="small text-muted mt-n2 mb-0">
                            {{ __('app.assets.no_type_owner') }}
                            @if ($isOwner)
                                <a href="{{ route('settings.types') }}" wire:navigate>{{ __('app.assets.add_custom_type') }}</a>
                            @else
                                {{ __('app.assets.no_type_member_short') }}
                            @endif
                        </p>
                        @error('assetTypeId') <div class="invalid-feedback d-block mt-2 mb-0">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <x-ui.input :label="__('app.fields.asset_name')" wire:model="name" placeholder="example.com" />
                        @error('name') <div class="invalid-feedback d-block mt-n3 mb-0">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <x-ui.input :label="__('app.fields.expires')" type="date" wire:model="expiresAt" />
                    </div>
                    <div class="col-md-4">
                        <x-ui.select :label="__('app.fields.auto_renew')" wire:model="autoRenew">
                            @foreach (__('app.enums.auto_renew') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="col-md-4">
                        <x-ui.select :label="__('app.fields.owner')" wire:model="owner">
                            @foreach (__('app.enums.party') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="col-md-4">
                        <x-ui.select :label="__('app.fields.payer')" wire:model="payer">
                            @foreach (__('app.enums.party') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="col-md-6">
                        <x-ui.input :label="__('app.fields.renewal_cost')" type="number" min="0" step="0.01" wire:model="renewalCost" />
                        @error('renewalCost') <div class="invalid-feedback d-block mt-n3 mb-0">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <x-ui.select :label="__('app.fields.currency')" wire:model="currency">
                            @foreach (\App\Support\UpcomingPayments::CURRENCIES as $code)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                            @if ($currency !== '' && ! in_array($currency, \App\Support\UpcomingPayments::CURRENCIES, true))
                                <option value="{{ $currency }}">{{ $currency }}</option>
                            @endif
                        </x-ui.select>
                    </div>
                    <div class="col-md-6">
                        <x-ui.input :label="__('app.fields.notice_email')" type="email" wire:model="noticeEmail" />
                        @error('noticeEmail') <div class="invalid-feedback d-block mt-n3 mb-0">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <x-ui.input
                            :label="__('app.fields.override_days')"
                            wire:model="overrideDays"
                            placeholder="{{ $workspaceDays->implode(', ') }}"
                        />
                        <p class="small text-muted mt-n2 mb-0">
                            {{ __('app.assets.workspace_rules') }}
                            <strong>{{ $workspaceDays->implode(', ') ?: '30, 14, 7, 1' }}</strong> {{ __('app.assets.days_short') }}
                            @if ($isOwner)
                                <a href="{{ route('settings.reminders') }}" wire:navigate>{{ __('app.assets.change_rules') }}</a>
                            @endif
                        </p>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model="sslCheckEnabled" id="sslCheckEnabled">
                            <label class="form-check-label" for="sslCheckEnabled">{{ __('app.fields.ssl_check') }}</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <x-ui.textarea :label="__('app.fields.notes')" wire:model="notes" rows="4" placeholder="{{ __('app.assets.notes_placeholder') }}" />
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <x-ui.button variant="accent" type="submit">{{ $editingId ? __('app.common.save') : __('app.dashboard.add_asset') }}</x-ui.button>
                    <x-ui.button href="{{ $backUrl }}" wire:navigate>{{ __('app.common.cancel') }}</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
