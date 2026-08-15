@props(['label' => null, 'groupClass' => 'mb-3'])

@php
    $isDate = ($attributes->get('type') ?? 'text') === 'date';
@endphp

@if ($label)
    <div class="{{ $groupClass }}">
        <label class="form-label">{{ $label }}</label>
@endif

@if ($isDate)
    <div
        class="ny-dp"
        x-data="nyDatepicker({
            locale: @js(str_replace('_', '-', app()->getLocale())),
            todayLabel: @js(__('app.common.today')),
            clearLabel: @js(__('app.common.clear_date')),
            placeholder: @js(__('app.fields.date_placeholder')),
        })"
        @keydown.escape.window="onEsc()"
        @click.outside="open = false"
    >
        <button type="button" class="form-control ny-dp-trigger" x-ref="trigger" @click="toggle()">
            <span class="ny-dp-value" :class="{ 'is-placeholder': !iso }" x-text="display || placeholder"></span>
            <i class="mdi mdi-calendar-month-outline"></i>
        </button>
        <input type="hidden" {{ $attributes->except('type') }} x-ref="input">
        <div class="ny-dp-pop" x-ref="pop" x-show="open" x-cloak x-transition.opacity.duration.120ms>
            <div class="ny-dp-head">
                <button type="button" class="ny-dp-nav" @click="shift(-1)">
                    <i class="mdi mdi-chevron-left"></i>
                </button>
                <div class="ny-dp-title">
                    <template x-if="panel === 'days'">
                        <div class="ny-dp-title-row">
                            <button type="button" class="ny-dp-title-btn" @click="openMonths()" x-text="monthLabel"></button>
                            <button type="button" class="ny-dp-title-btn" @click="openYears()" x-text="yearLabel"></button>
                        </div>
                    </template>
                    <template x-if="panel === 'months'">
                        <button type="button" class="ny-dp-title-btn" @click="openYears()" x-text="yearLabel"></button>
                    </template>
                    <template x-if="panel === 'years'">
                        <button type="button" class="ny-dp-title-btn" @click="panel = 'days'" x-text="yearRangeTitle"></button>
                    </template>
                </div>
                <button type="button" class="ny-dp-nav" @click="shift(1)">
                    <i class="mdi mdi-chevron-right"></i>
                </button>
            </div>
            <div x-show="panel === 'days'">
                <div class="ny-dp-week">
                    <template x-for="(w, i) in weekdays" :key="i">
                        <span x-text="w"></span>
                    </template>
                </div>
                <div class="ny-dp-grid">
                    <template x-for="cell in cells" :key="cell.iso">
                        <button
                            type="button"
                            class="ny-dp-day"
                            :class="{ 'is-muted': !cell.inMonth, 'is-today': cell.isToday, 'is-selected': cell.isSelected }"
                            @click="pick(cell.iso)"
                            x-text="cell.day"
                        ></button>
                    </template>
                </div>
            </div>
            <div class="ny-dp-chips" x-show="panel === 'months'" x-cloak>
                <template x-for="month in months" :key="month.index">
                    <button
                        type="button"
                        class="ny-dp-chip"
                        :class="{ 'is-selected': month.isSelected, 'is-today': month.isCurrent }"
                        @click="pickMonth(month.index)"
                        x-text="month.label"
                    ></button>
                </template>
            </div>
            <div class="ny-dp-chips" x-show="panel === 'years'" x-cloak>
                <template x-for="item in years" :key="item.year">
                    <button
                        type="button"
                        class="ny-dp-chip"
                        :class="{ 'is-selected': item.isSelected, 'is-today': item.isCurrent }"
                        @click="pickYear(item.year)"
                        x-text="item.year"
                    ></button>
                </template>
            </div>
            <div class="ny-dp-foot">
                <button type="button" class="ny-dp-link" @click="clearDate()" x-text="clearLabel"></button>
                <button type="button" class="ny-dp-link" @click="pickToday()" x-text="todayLabel"></button>
            </div>
        </div>
    </div>
@else
    <input {{ $attributes->class('form-control') }}>
@endif

@if ($label)
    </div>
@endif
