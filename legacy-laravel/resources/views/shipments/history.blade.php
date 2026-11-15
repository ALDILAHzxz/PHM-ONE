<x-app-layout title="Riwayat Pengiriman" page-title="Riwayat Pengiriman">
    <div class="space-y-5">
        <x-page-header title="Riwayat Pengiriman"
                       subtitle="Pengiriman yang sudah diterima mitra atau dibatalkan." />

        @forelse ($shipments as $shipment)
            <article class="phm-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="phm-helper">{{ $shipment->shipment_number }}</p>
                        <h2 class="phm-card-heading mt-0.5">{{ $shipment->order->partner->name }}</h2>
                        <p class="phm-helper mt-1">
                            {{ $shipment->order->product->name }} ·
                            {{ number_format($shipment->quantity_shipped, 0, ',', '.') }}
                            {{ $shipment->order->product->unit }}
                        </p>
                    </div>
                    <span class="phm-badge shrink-0 {{ $shipment->statusBadgeClasses() }}">
                        {{ $shipment->statusLabel() }}
                    </span>
                </div>

                <dl class="mt-4">
                    <x-info-row label="Surat jalan" :value="$shipment->delivery_note_number ?? '—'" />
                    <x-info-row label="Driver" :value="$shipment->driver?->full_name ?? '—'" />
                    <x-info-row label="Dikirim"
                                :value="$shipment->shipped_at?->translatedFormat('d M Y · H:i') ?? '—'" />
                    <x-info-row label="Diterima"
                                :value="$shipment->delivered_at?->translatedFormat('d M Y · H:i') ?? '—'" />
                    <x-info-row label="Dicatat oleh" :value="$shipment->recorder?->full_name ?? '—'" />
                    @if ($shipment->notes)
                        <x-info-row label="Catatan" :value="$shipment->notes" />
                    @endif
                </dl>
            </article>
        @empty
            <x-empty-state icon="history"
                           title="Belum ada riwayat"
                           message="Belum ada pengiriman yang selesai." />
        @endforelse

        @if ($shipments->hasPages())
            <div class="phm-card py-4">{{ $shipments->links() }}</div>
        @endif
    </div>
</x-app-layout>
