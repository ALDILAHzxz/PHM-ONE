@props([
    'label',
    'value',
    'unit' => null,
    'hint' => null,
    'icon' => null,
    'tone' => 'default', // default | positive | warning | critical
    'href' => null,
])

@php
    $toneClasses = match ($tone) {
        'positive' => 'text-emerald-700 dark:text-emerald-300',
        'warning' => 'text-amber-700 dark:text-amber-300',
        'critical' => 'text-rose-700 dark:text-rose-300',
        default => 'text-body',
    };

    $iconWrap = match ($tone) {
        'positive' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
        'critical' => 'bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300',
        default => 'bg-brand/10 text-interactive',
    };

    // Only a card that actually navigates gets the hover lift (section 29).
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['phm-card flex flex-col gap-3', 'phm-card-interactive' => (bool) $href]) }}>

    <div class="flex items-start justify-between gap-3">
        <p class="text-sm leading-snug font-medium text-muted">{{ $label }}</p>
        @if ($icon)
            <span class="grid size-9 shrink-0 place-items-center rounded-[0.625rem] {{ $iconWrap }}">
                <x-dynamic-component :component="'lucide-' . $icon" class="size-5" aria-hidden="true" />
            </span>
        @endif
    </div>

    <p class="flex items-baseline gap-1.5">
        <span class="phm-kpi {{ $toneClasses }}">{{ $value }}</span>
        @if ($unit)
            <span class="text-sm font-medium text-muted">{{ $unit }}</span>
        @endif
    </p>

    @if ($hint)
        <p class="phm-helper">{{ $hint }}</p>
    @endif

    @if ($href)
        <span class="mt-auto flex items-center gap-1 text-sm font-semibold text-interactive">
            Lihat detail
            <x-lucide-chevron-right class="size-4" aria-hidden="true" />
        </span>
    @endif
</{{ $tag }}>
