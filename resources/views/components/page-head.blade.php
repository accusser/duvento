@props(['title' => '', 'sub' => null])

<div class="page-head">
    <div>
        <h1>{{ $title }}</h1>
        @if ($sub)
            <p class="lead-sub">{{ $sub }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="page-head-actions">{{ $slot }}</div>
    @endif
</div>
