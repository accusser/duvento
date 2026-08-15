<div>
    <x-page-head :title="__('app.team.title')" :sub="__('app.team.sub')" />

    @error('role') <div class="alert alert-warning">{{ $message }}</div> @enderror

    <div class="card mb-3">
        <div class="card-header"><h5>{{ __('app.team.invite') }}</h5></div>
        <div class="card-body">
            <form wire:submit="invite" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <x-ui.input :label="__('app.fields.email')" type="email" wire:model="email" />
                    @error('email') <div class="invalid-feedback d-block mt-n3">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <x-ui.select :label="__('app.fields.role')" wire:model="role">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="col-md-3">
                    <x-ui.button variant="accent" type="submit">{{ __('app.team.invite') }}</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>{{ __('app.team.members') }}</h5></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.fields.name') }}</th>
                        <th>{{ __('app.fields.email') }}</th>
                        <th>{{ __('app.fields.role') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->email }}</td>
                            <td>
                                <select class="form-select form-select-sm" wire:change="changeRole({{ $member->id }}, $event.target.value)">
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($member->pivot->role === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-end">
                                @if ($member->id !== auth()->id())
                                    <x-ui.button variant="danger" type="button" wire:click="remove({{ $member->id }})" wire:confirm="{{ __('app.team.remove_confirm') }}">
                                        {{ __('app.team.remove') }}
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">{{ __('app.team.empty_members') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>{{ __('app.team.invites') }}</h5></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('app.fields.email') }}</th>
                        <th>{{ __('app.fields.role') }}</th>
                        <th>{{ __('app.team.expires') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invites as $invite)
                        <tr>
                            <td>{{ $invite->email }}</td>
                            <td>{{ $invite->role->label() }}</td>
                            <td>{{ $invite->expires_at?->toDateString() }}</td>
                            <td class="text-end">
                                <x-ui.button variant="danger" type="button" wire:click="revoke({{ $invite->id }})">{{ __('app.team.revoke') }}</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">{{ __('app.team.empty_invites') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
