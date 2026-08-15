@props(['title' => null])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? config('app.name') }}</title>
<style>
    html, body { background-color: #fff; }
    html[data-bs-theme="dark"], html[data-bs-theme="dark"] body { background-color: #2a2d2d; }
</style>
<script>
(() => {
    const apply = () => {
        let cfg = {};
        try {
            const parsed = JSON.parse(localStorage.getItem('nyvora-config') || '{}');
            cfg = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
        } catch (e) {}
        let mode = ['light', 'dark', 'system'].includes(cfg.mode) ? cfg.mode : 'light';
        const direction = ['ltr', 'rtl'].includes(cfg.direction) ? cfg.direction : 'ltr';
        if (mode === 'system') {
            mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-bs-theme', mode);
        document.documentElement.setAttribute('dir', direction);
        document.cookie = 'nyvora-theme=' + mode + ';path=/;max-age=31536000;SameSite=Lax';
        if (document.body) {
            const isApp = !!document.querySelector('.app') || document.body.classList.contains('layout-boxed');
            const layout = cfg.layout === 'mini' ? 'boxed' : (cfg.layout || 'boxed');
            const mini = !!(cfg.sidebarMini || cfg.layout === 'mini') && layout !== 'horizontal';
            document.body.classList.toggle('sidebar-mini', mini);
            document.body.classList.toggle('layout-horizontal', layout === 'horizontal');
            document.body.classList.toggle('layout-boxed', layout !== 'horizontal' && isApp);
        }
    };
    apply();
    if (window.__nyThemeBoot) {
        return;
    }
    window.__nyThemeBoot = true;
    document.addEventListener('livewire:navigating', apply);
    document.addEventListener('livewire:navigated', apply);
})();
</script>
<link rel="icon" href="{{ asset('theme/images/logo.svg') }}">
<link rel="stylesheet" href="{{ asset('theme/css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('theme/vendor/bootstrap/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('theme/vendor/mdi/css/materialdesignicons.min.css') }}">
<link rel="stylesheet" href="{{ asset('theme/vendor/remixicon/remixicon.css') }}">
<link rel="stylesheet" href="{{ asset('theme/css/app.css') }}?v={{ filemtime(public_path('theme/css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('theme/css/duvento.css') }}?v={{ filemtime(public_path('theme/css/duvento.css')) }}">
@livewireStyles
