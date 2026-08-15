@props(['attachment'])

@php
    $url = route('ticket-attachments.download', $attachment);
    $size = \Illuminate\Support\Number::fileSize($attachment->size);
@endphp

@if ($attachment->isImage())
    <a href="{{ $url }}" class="support-attachment-image" data-lightbox title="{{ $attachment->file_name }}">
        <img src="{{ $url }}" alt="{{ $attachment->file_name }}" loading="lazy">
    </a>
@else
    <a
        href="{{ $url }}"
        class="support-attachment"
        @if ($attachment->opensInBrowser()) target="_blank" rel="noopener" @endif
    >
        <i class="mdi {{ $attachment->isPdf() ? 'mdi-file-pdf-box' : 'mdi-paperclip' }}"></i>
        <span>{{ $attachment->file_name }}</span>
        <small>{{ $size }}</small>
    </a>
@endif
