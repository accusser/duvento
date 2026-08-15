@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'value' => null,
    'groupClass' => 'mb-3',
    'title' => null,
])

@php
    $selected = $value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
        ? $value->getClientOriginalName()
        : '';
@endphp

<div class="{{ $groupClass }}">
    @if ($label)
        <label class="form-label">{{ $label }}</label>
    @endif

    <div class="ny-file" @if ($title) title="{{ $title }}" @endif x-data="{ name: @js($selected) }">
        <input
            type="file"
            {{ $attributes->class('ny-file-native') }}
            x-ref="input"
            @change="name = $refs.input.files[0]?.name || ''"
        >
        <button type="button" class="ny-file-btn" @click="$refs.input.click()">
            {{ __('app.common.choose_file') }}
        </button>
        <span class="ny-file-name" :class="{ 'is-placeholder': !name }" x-text="name || @js(__('app.common.no_file'))"></span>
    </div>

    @if ($hint)
        <div class="form-text">{{ $hint }}</div>
    @endif

    @if ($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif
</div>
