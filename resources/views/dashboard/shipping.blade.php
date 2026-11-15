<x-app-layout title="Pengiriman" page-title="Pengiriman">
    <div class="space-y-6">
        <x-page-header title="Pengiriman"
                       subtitle="Kinerja pengiriman barang ke mitra."
                       :updated-at="$summary['as_of']->translatedFormat('H:i')" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Dikirim hari ini"
                         :value="number_format($summary['today_quantity'], 0, ',', '.')"
                         unit="pcs" icon="truck"
                         :hint="$summary['today_count'] . ' pengiriman'" />
            <x-stat-card label="Dalam perjalanan"
                         :value="$summary['in_transit']"
                         unit="pengiriman" icon="map-pin" />
            <x-stat-card label="Sudah diterima"
                         :value="$summary['received']"
                         unit="pengiriman" icon="circle-check" tone="positive"
                         :hint="number_format($summary['total_delivered_quantity'], 0, ',', '.') . ' pcs total'" />
            <x-stat-card label="Bermasalah"
                         :value="$summary['issues']"
                         unit="pengiriman" icon="triangle-alert"
                         :tone="$summary['issues'] > 0 ? 'critical' : 'positive'" />
        </div>

        <section aria-labelledby="judul-daftar">
            <h2 id="judul-daftar" class="phm-section-heading">Daftar Pengiriman</h2>

            <div class="mt-4 space-y-3">
                @forelse ($shipments as $shipment)
                    <article class="phm-card">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="phm-helper">{{ $shipment->shipment_number }}</p>
                                <h3 class="phm-card-heading mt-0.5">
                                    {{ $shipment->order->partner->name }}
                                </h3>
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
                            <x-info-row label="Driver" :value="$shipment->driver?->full_name ?? 'Belum ditentukan'" />
                            <x-info-row label="Tujuan" :value="$shipment->destination ?? '—'" />
                            <x-info-row label="Dikirim"
                                        :value="$shipment->shipped_at?->translatedFormat('d M Y · H:i') ?? 'Belum dikirim'" />
                            @if ($shipment->delivered_at)
                                <x-info-row label="Diterima"
                                            :value="$shipment->delivered_at->translatedFormat('d M Y · H:i')" />
                            @endif
                        </dl>
                    </article>
                @empty
                    <x-empty-state icon="truck"
                                   title="Belum ada pengiriman"
                                   message="Belum ada pengiriman yang tercatat." />
                @endforelse
            </div>

            @if ($shipments->hasPages())
                <div class="phm-card mt-4 py-4">{{ $shipments->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
