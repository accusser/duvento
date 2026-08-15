@props([
    'variant' => 'ghost',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'tip' => null,
])

@php
    $classes = 'btn ';
    $classes .= match ($variant) {
        'accent' => 'btn-primary',
        'danger' => 'btn-outline-danger',
        'soft' => 'btn-soft-primary',
        default => 'btn-outline-secondary',
    };
    if ($icon) {
        $classes .= ' btn-icon btn-sm';
    }
    $iconClass = $icon && ! str_starts_with($icon, 'mdi-') ? 'mdi-'.$icon : $icon;
    $tipTone = match ($variant) {
        'danger' => 'danger',
        'soft', 'accent' => 'primary',
        default => null,
    };
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($tip) data-tip @if ($tipTone) data-tip-tone="{{ $tipTone }}" @endif aria-label="{{ $tip }}" @endif {{ $attributes->class($classes) }}>
        @if ($icon)<i class="mdi {{ $iconClass }}"></i>@endif{{ $slot }}@if ($tip)<span class="ny-tip">{{ $tip }}</span>@endif
    </a>
@else
    <button type="{{ $type }}" @if ($tip) data-tip @if ($tipTone) data-tip-tone="{{ $tipTone }}" @endif aria-label="{{ $tip }}" @endif {{ $attributes->class($classes) }}>
        @if ($icon)<i class="mdi {{ $iconClass }}"></i>@endif{{ $slot }}@if ($tip)<span class="ny-tip">{{ $tip }}</span>@endif
    </button>
@endif
