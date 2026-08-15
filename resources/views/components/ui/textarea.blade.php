@props(['label' => null, 'groupClass' => 'mb-3'])

@if ($label)
    <div class="{{ $groupClass }}">
        <label class="form-label">{{ $label }}</label>
        <textarea {{ $attributes->class('form-control') }}>{{ $slot }}</textarea>
    </div>
@else
    <textarea {{ $attributes->class('form-control') }}>{{ $slot }}</textarea>
@endif
