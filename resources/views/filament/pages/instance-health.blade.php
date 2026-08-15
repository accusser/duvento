<x-filament-panels::page>
    <div class="row g-3 mb-3">
        @foreach ($checks as $check)
            <div class="col-md-6 col-xl">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">{{ $check['label'] }}</div>
                            <span @class([
                                'badge',
                                'badge-soft-success' => ($check['level'] ?? ($check['ok'] ? 'ok' : 'stale')) === 'ok',
                                'badge-soft-warning' => ($check['level'] ?? 'stale') !== 'ok',
                            ])>
                                @if (($check['level'] ?? null) === 'warn')
                                    {{ __('admin.health.warn') }}
                                @else
                                    {{ $check['ok'] ? __('admin.health.ok') : __('admin.health.stale') }}
                                @endif
                            </span>
                        </div>
                        <p class="small text-muted mb-0">{{ $check['detail'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('admin.health.cron_title') }}</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">{{ __('admin.health.cron_help') }}</p>
            <div class="input-group" x-data>
                <input
                    type="text"
                    class="form-control font-monospace small"
                    readonly
                    value="{{ $cron }}"
                    x-ref="cron"
                >
                <button
                    type="button"
                    class="btn btn-light"
                    x-on:click="navigator.clipboard.writeText($refs.cron.value)"
                >
                    {{ __('admin.health.cron_copy') }}
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('admin.health.mail_title') }}</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">{{ __('admin.health.mail_help') }}</p>
            <form wire:submit="saveMail" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('admin.health.mailer') }}</label>
                    <select class="form-select @error('mailer') is-invalid @enderror" wire:model.live="mailer">
                        <option value="log">{{ __('admin.health.mailer_log') }}</option>
                        <option value="smtp">SMTP</option>
                    </select>
                    @error('mailer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('admin.health.from_address') }}</label>
                    <input type="email" class="form-control @error('from_address') is-invalid @enderror" wire:model="from_address">
                    @error('from_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('admin.health.from_name') }}</label>
                    <input type="text" class="form-control @error('from_name') is-invalid @enderror" wire:model="from_name">
                    @error('from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if ($mailer === 'smtp')
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.health.host') }}</label>
                        <input type="text" class="form-control @error('host') is-invalid @enderror" wire:model="host" placeholder="smtp.example.com">
                        @error('host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.health.port') }}</label>
                        <input type="number" class="form-control @error('port') is-invalid @enderror" wire:model="port">
                        @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.health.scheme') }}</label>
                        <select class="form-select @error('scheme') is-invalid @enderror" wire:model="scheme">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="">{{ __('admin.health.scheme_none') }}</option>
                        </select>
                        @error('scheme') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.health.username') }}</label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" wire:model="username" autocomplete="off">
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.health.password') }}</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" wire:model="password" autocomplete="new-password" placeholder="{{ __('admin.health.password_keep') }}">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="col-12">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        {{ __('admin.health.mail_save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('admin.health.failed_jobs') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.fields.when') }}</th>
                        <th>{{ __('admin.health.queue_name') }}</th>
                        <th>{{ __('admin.health.exception') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($failedJobs as $job)
                        <tr>
                            <td>{{ $job['failed_at'] }}</td>
                            <td>{{ $job['queue'] }}</td>
                            <td class="small text-muted">{{ $job['exception'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted">{{ __('admin.health.failed_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
