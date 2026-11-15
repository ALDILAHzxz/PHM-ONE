@props([
    'title',
    'subtitle' => null,
    'updatedAt' => null,
])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <h1 class="phm-page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 text-base text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="flex items-center gap-3">
        {{-- Data freshness, so nobody mistakes a cached screen for live data. --}}
        @if ($updatedAt)
            <p class="phm-helper flex items-center gap-1.5">
                <x-lucide-clock class="size-4 shrink-0" aria-hidden="true" />
                <span>Diperbarui {{ $updatedAt }}</span>
            </p>
        @endif

        {{ $actions ?? '' }}
    </div>
</div>
