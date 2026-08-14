@props([
    'variant' => 'ghost',
    'type' => 'button',
    'href' => null,
])

@php
    $classes = 'inline-flex items-center justify-center rounded-[10px] px-3.5 py-2 text-sm font-medium transition duration-150 disabled:opacity-50 ';
    $classes .= match ($variant) {
        'accent' => 'bg-accent text-white hover:opacity-90',
        'danger' => 'border border-critical text-critical hover:bg-critical/10',
        default => 'border border-border text-ink hover:border-brand',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
