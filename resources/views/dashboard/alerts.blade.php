<x-app-layout title="Peringatan" page-title="Peringatan">
    <div class="space-y-5">
        <x-page-header title="Peringatan"
                       subtitle="Hal-hal yang perlu perhatian, diurutkan dari yang paling penting."
                       :updated-at="now()->translatedFormat('H:i')" />

        @forelse ($alerts as $alert)
            @php
                $style = match ($alert['severity']) {
                    'critical' => [
                        'card' => 'border-rose-300 bg-rose-50 dark:border-rose-400/40 dark:bg-rose-400/10',
                        'icon' => 'text-rose-700 dark:text-rose-300',
                        'label' => 'Penting',
                        'badge' => 'bg-rose-100 text-rose-900 ring-rose-300 dark:bg-rose-400/20 dark:text-rose-100 dark:ring-rose-400/40',
                    ],
                    'warning' => [
                        'card' => 'border-amber-300 bg-amber-50 dark:border-amber-400/40 dark:bg-amber-400/10',
                        'icon' => 'text-amber-700 dark:text-amber-300',
                        'label' => 'Perhatian',
                        'badge' => 'bg-amber-100 text-amber-900 ring-amber-300 dark:bg-amber-400/20 dark:text-amber-100 dark:ring-amber-400/40',
                    ],
                    default => [
                        'card' => 'border-line bg-surface',
                        'icon' => 'text-interactive',
                        'label' => 'Info',
                        'badge' => 'bg-brand/15 text-interactive ring-brand/30',
                    ],
                };
            @endphp

            <article class="rounded-[var(--radius-card)] border p-5 {{ $style['card'] }}">
                <div class="flex items-start gap-3">
                    <x-lucide-triangle-alert class="mt-0.5 size-6 shrink-0 {{ $style['icon'] }}" aria-hidden="true" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="phm-badge {{ $style['badge'] }}">{{ $style['label'] }}</span>
                        </div>
                        <h2 class="phm-card-heading mt-2">{{ $alert['title'] }}</h2>
                        <p class="mt-1 text-base leading-relaxed text-muted">{{ $alert['detail'] }}</p>

                        @if ($alert['route'] && $alert['route_param'])
                            <a href="{{ route($alert['route'], $alert['route_param']) }}"
                               class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-interactive">
                                Lihat detail
                                <x-lucide-chevron-right class="size-4" aria-hidden="true" />
                            </a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <x-empty-state icon="circle-check"
                           title="Tidak ada peringatan"
                           message="Semua pesanan dalam jadwal, tidak ada kendala produksi atau pengiriman bermasalah." />
        @endforelse
    </div>
</x-app-layout>
