<x-layouts.guest title="Duvento — Deadline tracking for agencies">
    <main class="landing-page">
        <div class="container">
            <header class="landing-top">
                <a href="{{ url('/') }}" class="brand" aria-label="Duvento home">
                    <span class="brand-mark">D</span>
                    <span class="brand-text">Duvento</span>
                </a>

                <nav class="landing-nav" aria-label="Portal navigation">
                    <span class="badge badge-soft-primary text-uppercase">{{ $edition }}</span>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-account-outline me-1"></i>Client portal
                    </a>
                    <a href="{{ url('/admin') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-shield-account-outline me-1"></i>Admin portal
                    </a>
                </nav>
            </header>

            <section class="landing-hero">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <span class="landing-eyebrow"><i class="mdi mdi-radar"></i> Deadline control center</span>
                        <h1>Nothing expires unnoticed.</h1>
                        <p class="landing-lead">
                            Keep client domains, SSL certificates, hosting plans, and software licenses in one calm, reliable workspace.
                        </p>

                        <div class="landing-actions">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                                Open client portal <i class="mdi mdi-arrow-right ms-1"></i>
                            </a>
                            <a href="{{ url('/admin') }}" class="btn btn-outline-secondary btn-lg">
                                Open admin portal
                            </a>
                        </div>

                        <div class="landing-proof">
                            <span><i class="mdi mdi-check-circle"></i>Email reminders</span>
                            <span><i class="mdi mdi-check-circle"></i>SSL monitoring</span>
                            <span><i class="mdi mdi-check-circle"></i>CSV export</span>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="landing-preview card">
                            <div class="card-header">
                                <div>
                                    <span class="landing-preview-label">Live overview</span>
                                    <h5>Upcoming deadlines</h5>
                                </div>
                                <span class="badge badge-soft-primary">Demo</span>
                            </div>
                            <div class="list-group list-group-flush">
                                @foreach ($assets as $asset)
                                    <x-asset-countdown :asset="$asset" />
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="portal-section" aria-labelledby="portal-heading">
                <div class="section-heading">
                    <span class="landing-eyebrow">Choose your workspace</span>
                    <h2 id="portal-heading">Two portals, one clear system</h2>
                    <p>Use the client workspace for daily deadline management or the admin area for system-wide control.</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <a href="{{ route('dashboard') }}" class="portal-card">
                            <span class="portal-icon"><i class="mdi mdi-view-dashboard-outline"></i></span>
                            <span class="portal-copy">
                                <small>FOR TEAMS AND CLIENTS</small>
                                <strong>Client portal</strong>
                                <span>Manage clients, assets, reminders, reports, and account settings.</span>
                            </span>
                            <i class="mdi mdi-arrow-right portal-arrow"></i>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ url('/admin') }}" class="portal-card portal-card-admin">
                            <span class="portal-icon"><i class="mdi mdi-shield-crown-outline"></i></span>
                            <span class="portal-copy">
                                <small>FOR ADMINISTRATORS</small>
                                <strong>Admin portal</strong>
                                <span>Control users, workspaces, system health, and operational data.</span>
                            </span>
                            <i class="mdi mdi-arrow-right portal-arrow"></i>
                        </a>
                    </div>
                </div>
            </section>

            @if (\App\Support\Edition::isCloud())
                <section class="cloud-plans" aria-labelledby="plans-heading">
                    <div class="section-heading">
                        <span class="landing-eyebrow">Managed cloud</span>
                        <h2 id="plans-heading">Start with a 14-day trial</h2>
                        <p>No card required. Choose Starter for a small team or Agency for a growing client portfolio.</p>
                    </div>
                    <div class="row g-4 align-items-stretch">
                        <div class="col-md-4">
                            <div class="card pricing-card h-100">
                                <span class="badge badge-soft-secondary mb-2">OPEN SOURCE</span>
                                <h4>Self-host</h4>
                                <p class="text-muted">Install and maintain Duvento on your own server.</p>
                                <div class="pricing-price">$0<small>/forever</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card pricing-card featured h-100">
                                <span class="badge bg-white text-primary mb-2">MOST POPULAR</span>
                                <h4>Starter</h4>
                                <p class="ny-op-9">Deadline tracking for up to 25 clients.</p>
                                <div class="pricing-price">$19<small>/month</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card pricing-card h-100">
                                <span class="badge badge-soft-warning mb-2">FOR AGENCIES</span>
                                <h4>Agency</h4>
                                <p class="text-muted">Up to 100 clients with white-label reports.</p>
                                <div class="pricing-price">$49<small>/month</small></div>
                            </div>
                        </div>
                    </div>
                    <form class="cloud-waitlist card" method="POST" action="{{ route('waitlist.store') }}">
                        @csrf
                        <div>
                            <strong>Want early access?</strong>
                            <span>Leave your details and we will keep you posted.</span>
                        </div>
                        <x-ui.input name="name" placeholder="Name" />
                        <x-ui.input name="email" type="email" placeholder="Email address" required />
                        <x-ui.button variant="accent" type="submit">Join waitlist</x-ui.button>
                        @if (session('status'))
                            <p class="text-success small mb-0">{{ session('status') }}</p>
                        @endif
                    </form>
                </section>
            @endif

            <section class="landing-info">
                <div>
                    <i class="mdi mdi-server-outline"></i>
                    <span>
                        <strong>{{ \App\Support\Edition::isCloud() ? 'Managed cloud edition' : 'Self-hosted edition' }}</strong>
                        {{ \App\Support\Edition::isCloud()
                            ? 'A hosted workspace with no server maintenance required.'
                            : 'Free AGPLv3 software powered by PHP, MySQL or SQLite, and cron.' }}
                    </span>
                </div>
                <a href="https://duvento.com" target="_blank" rel="noopener noreferrer">
                    Duvento.com <i class="mdi mdi-open-in-new"></i>
                </a>
            </section>

            <footer class="app-footer">
                <span>© {{ now()->year }} <a href="https://duvento.com" target="_blank" rel="noopener noreferrer">Duvento</a></span>
                <span>Deadline tracking without the noise.</span>
            </footer>
        </div>
    </main>
</x-layouts.guest>
