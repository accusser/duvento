@props(['label' => null, 'groupClass' => 'mb-3'])

@if ($label)
    <div class="{{ $groupClass }}">
        <label class="form-label">{{ $label }}</label>
        <select {{ $attributes->class('form-select') }}>
            {{ $slot }}
        </select>
    </div>
@else
    <select {{ $attributes->class('form-select') }}>
        {{ $slot }}
    </select>
@endif
