@props([
    'icon' => 'inbox',
    'title',
    'message' => null,
])

<div class="phm-card flex flex-col items-center gap-3 py-10 text-center">
    <span class="grid size-14 place-items-center rounded-full bg-surface-muted text-muted ring-1 ring-line">
        <x-dynamic-component :component="'lucide-' . $icon" class="size-7" aria-hidden="true" />
    </span>
    <p class="phm-card-heading">{{ $title }}</p>
    @if ($message)
        <p class="max-w-md text-base text-muted">{{ $message }}</p>
    @endif
    {{ $slot }}
</div>
