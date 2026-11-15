@props([
    'label',
    'value' => null,
    'strong' => false,
])

<div class="flex items-start justify-between gap-4 border-b border-line py-2.5 last:border-0">
    <dt class="text-base text-muted">{{ $label }}</dt>
    <dd @class([
        'text-right text-base tabular-nums',
        'font-semibold text-body' => $strong,
        'font-medium' => ! $strong,
    ])>
        {{ $value ?? $slot }}
    </dd>
</div>
