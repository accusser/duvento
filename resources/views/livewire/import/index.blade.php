<div>
    <x-page-head :title="__('app.import.title')" :sub="__('app.import.sub')">
        <x-ui.button href="{{ route('export.clients.template') }}">{{ __('app.import.template_clients') }}</x-ui.button>
        <x-ui.button href="{{ route('export.assets.template') }}">{{ __('app.import.template_assets') }}</x-ui.button>
    </x-page-head>

    <div class="card">
        <div class="card-body">
            <form wire:submit="import">
                <x-ui.select :label="__('app.import.target')" wire:model="target">
                    <option value="clients">{{ __('app.import.target_clients') }}</option>
                    <option value="assets">{{ __('app.import.target_assets') }}</option>
                </x-ui.select>

                <x-ui.file
                    :label="__('app.fields.file')"
                    :value="$file"
                    :error="$errors->first('file') ?: $errors->first('name')"
                    wire:model="file"
                    accept=".csv,text/csv"
                />

                @if ($target === 'clients')
                    <p class="small text-muted">{{ __('app.import.hint_clients', ['cols' => 'name, contact, email, website, notes', 'ru' => 'имя, контакт, почта, сайт, заметки']) }}</p>
                @else
                    <p class="small text-muted">
                        {{ __('app.import.hint_assets', ['cols' => 'name, type, client, expires_at, owner, payer, auto_renew, notice_email, ssl_check, notes, renewal_cost, currency', 'client_id' => 'client_id', 'ssl' => 'ssl', 'domain' => 'domain']) }}
                    </p>
                @endif

                <x-ui.button variant="accent" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('app.import.submit') }}</span>
                    <span wire:loading>{{ __('app.import.loading') }}</span>
                </x-ui.button>
            </form>
        </div>
    </div>
</div>
