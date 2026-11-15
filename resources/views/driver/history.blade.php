<x-app-layout title="Riwayat Perjalanan" page-title="Riwayat Perjalanan">
    <div class="mx-auto w-full max-w-[42rem] space-y-5">
        <x-page-header title="Riwayat Perjalanan"
                       subtitle="Pengiriman yang sudah Anda selesaikan." />

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
                    <x-info-row label="Tujuan" :value="$shipment->destination ?? '—'" />
                    <x-info-row label="Berangkat"
                                :value="$shipment->shipped_at?->translatedFormat('d M Y · H:i') ?? '—'" />
                    <x-info-row label="Diterima"
                                :value="$shipment->delivered_at?->translatedFormat('d M Y · H:i') ?? '—'" />
                    @php $received = $shipment->events->firstWhere('recipient_name', '!=', null); @endphp
                    @if ($received)
                        <x-info-row label="Nama penerima" :value="$received->recipient_name" />
                    @endif
                </dl>
            </article>
        @empty
            <x-empty-state icon="history"
                           title="Belum ada riwayat"
                           message="Anda belum menyelesaikan pengiriman." />
        @endforelse

        @if ($shipments->hasPages())
            <div class="phm-card py-4">{{ $shipments->links() }}</div>
        @endif
    </div>
</x-app-layout>
