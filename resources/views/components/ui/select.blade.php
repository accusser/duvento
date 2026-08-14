@props(['label' => null])

<label class="block">
    @if ($label)
        <span class="mb-1.5 block text-sm text-muted">{{ $label }}</span>
    @endif
    <select {{ $attributes->class('w-full rounded-[10px] border border-border bg-card px-3 py-2 text-sm text-ink') }}>
        {{ $slot }}
    </select>
</label>
