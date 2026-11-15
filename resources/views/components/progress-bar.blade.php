@props([
    'reported' => 0,
    'verified' => 0,
    'target' => 0,
    'unit' => 'pcs',
    'showLegend' => true,
])

@php
    $reportedPct = $target > 0 ? min(100, round($reported / $target * 100, 1)) : 0;
    $verifiedPct = $target > 0 ? min(100, round($verified / $target * 100, 1)) : 0;
@endphp

{{--
    Two-layer bar: the darker segment is VERIFIED, the lighter segment is
    REPORTED-but-not-yet-verified. Pending work is never drawn as verified.
--}}
<div {{ $attributes }}>
    <div class="h-3 w-full overflow-hidden rounded-full bg-surface-muted ring-1 ring-line ring-inset"
         role="img"
         aria-label="{{ $reported }} {{ $unit }} dilaporkan, {{ $verified }} {{ $unit }} terverifikasi, dari target {{ $target }} {{ $unit }}">
        <div class="relative h-full">
            <div class="absolute inset-y-0 left-0 rounded-full bg-sky-300 dark:bg-sky-400/50"
                 style="width: {{ $reportedPct }}%"></div>
            <div class="absolute inset-y-0 left-0 rounded-full bg-brand"
                 style="width: {{ $verifiedPct }}%"></div>
        </div>
    </div>

    @if ($showLegend)
        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[0.8125rem] text-muted">
            <span class="flex items-center gap-1.5">
                <span class="size-2.5 shrink-0 rounded-full bg-brand" aria-hidden="true"></span>
                Terverifikasi {{ number_format($verified, 0, ',', '.') }}
            </span>
            <span class="flex items-center gap-1.5">
                <span class="size-2.5 shrink-0 rounded-full bg-sky-300 dark:bg-sky-400/50" aria-hidden="true"></span>
                Dilaporkan {{ number_format($reported, 0, ',', '.') }}
            </span>
            <span>Target {{ number_format($target, 0, ',', '.') }} {{ $unit }}</span>
        </div>
    @endif
</div>
