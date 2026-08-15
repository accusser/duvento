@php
    $bsTheme = request()->cookie('nyvora-theme') === 'dark' ? 'dark' : 'light';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ $bsTheme }}" data-ny-scope="app">
<head>
    <x-layouts.theme-head :title="$title ?? config('app.name')" />
</head>
<body>
    <button class="icon-btn auth-theme-toggle" type="button" data-toggle="theme" data-tip aria-label="{{ __('app.header.theme') }}">
        <i class="mdi mdi-weather-night"></i>
        <span class="ny-tip">{{ __('app.header.theme') }}</span>
    </button>
    {{ $slot }}
    <x-layouts.theme-foot />
</body>
</html>
