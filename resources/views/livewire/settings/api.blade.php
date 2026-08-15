<div>
    <x-page-head :title="__('app.api.title')" :sub="__('app.api.sub')" />

    @if ($plainToken)
        <div class="alert alert-success">
            <p class="mb-1">{{ __('app.api.copy_token') }}</p>
            <code class="d-block text-break">{{ $plainToken }}</code>
        </div>
    @endif

    <form wire:submit="createToken" class="d-flex align-items-end gap-2 mb-4">
        <x-ui.input group-class="flex-grow-1 mb-0" :label="__('app.fields.token_name')" wire:model="tokenName" />
        <x-ui.button variant="accent" type="submit">{{ __('app.common.create') }}</x-ui.button>
    </form>

    <div class="card mb-4">
        <div class="list-group list-group-flush">
            @forelse ($tokens as $token)
                <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                    <span>{{ $token->name }} <code class="text-muted">{{ $token->created_at->toDateString() }}</code></span>
                    <x-ui.button variant="danger" icon="trash-can-outline" :tip="__('app.common.delete')" wire:click="deleteToken({{ $token->id }})" />
                </div>
            @empty
                <div class="ny-list-empty">{{ __('app.api.empty_tokens') }}</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form wire:submit="saveWebhook">
                <x-ui.input :label="__('app.fields.webhook_url')" wire:model="webhookUrl" placeholder="https://example.com/hooks/duvento" />
                @error('webhookUrl') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror
                @if ($webhook)
                    <p class="small text-muted">{{ __('app.api.secret') }} <code>{{ $webhook->secret }}</code></p>
                @endif
                <x-ui.button variant="accent" type="submit">{{ __('app.api.save_webhook') }}</x-ui.button>
            </form>
        </div>
    </div>
</div>
