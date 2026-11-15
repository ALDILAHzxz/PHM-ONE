<x-app-layout title="Pengiriman Hari Ini" page-title="Pengiriman">
    <div class="space-y-6">
        <x-page-header title="Pengiriman Hari Ini"
                       subtitle="Barang yang siap dikirim dan pengiriman yang sedang berjalan."
                       :updated-at="$summary['as_of']->translatedFormat('H:i')">
            <x-slot:actions>
                @can(\App\Support\Permissions::SHIPMENT_CREATE)
                    <a href="{{ route('shipments.create') }}" class="phm-btn phm-btn-primary">
                        <x-lucide-plus class="size-5" aria-hidden="true" />
                        Catat Pengiriman
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Dikirim hari ini"
                         :value="number_format($summary['today_quantity'], 0, ',', '.')"
                         unit="pcs" icon="truck"
                         :hint="$summary['today_count'] . ' pengiriman'" />
            <x-stat-card label="Siap dikirim"
                         :value="$summary['prepared']"
                         unit="pengiriman" icon="package" />
            <x-stat-card label="Dalam perjalanan"
                         :value="$summary['in_transit']"
                         unit="pengiriman" icon="map-pin" />
            <x-stat-card label="Bermasalah"
                         :value="$summary['issues']"
                         unit="pengiriman" icon="triangle-alert"
                         :tone="$summary['issues'] > 0 ? 'critical' : 'positive'" />
        </div>

        {{-- Goods available to ship: verified production minus already shipped --}}
        <section aria-labelledby="judul-siap">
            <h2 id="judul-siap" class="phm-section-heading">Barang Siap Dikirim</h2>
            <p class="phm-helper mt-1">
                Hanya produksi yang sudah diverifikasi supervisor dihitung sebagai barang siap kirim.
            </p>

            <div class="phm-card mt-4">
                @forelse ($readyOrders as $order)
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line py-3 first:pt-0 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="text-base font-medium">{{ $order->partner->name }}</p>
                            <p class="phm-helper mt-0.5">
                                {{ $order->order_number }} · {{ $order->product->name }} ·
                                target kirim {{ $order->delivery_target_date->translatedFormat('d M Y') }}
                            </p>
                        </div>
                        <p class="shrink-0 text-right">
                            <span class="block text-base font-semibold tabular-nums text-emerald-700 dark:text-emerald-300">
                                {{ number_format($order->readyToShipQuantity(), 0, ',', '.') }} {{ $order->product->unit }}
                            </span>
                            <span class="phm-helper">siap dikirim</span>
                        </p>
                    </div>
                @empty
                    <p class="py-4 text-center text-base text-muted">
                        Belum ada barang siap kirim. Produksi perlu diverifikasi supervisor terlebih dahulu.
                    </p>
                @endforelse
            </div>
        </section>

        {{-- Shipments in progress --}}
        <section aria-labelledby="judul-berjalan">
            <h2 id="judul-berjalan" class="phm-section-heading">Pengiriman Berjalan</h2>

            <div class="mt-4 space-y-3">
                @forelse ($active as $shipment)
                    <article class="phm-card">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="phm-helper">
                                    {{ $shipment->shipment_number }}
                                    @if ($shipment->delivery_note_number)
                                        · surat jalan {{ $shipment->delivery_note_number }}
                                    @endif
                                </p>
                                <h3 class="phm-card-heading mt-0.5">{{ $shipment->order->partner->name }}</h3>
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
                            <x-info-row label="Driver" :value="$shipment->driver?->full_name ?? 'Belum ditentukan'" />
                            <x-info-row label="Tujuan" :value="$shipment->destination ?? '—'" />
                            <x-info-row label="Dikirim"
                                        :value="$shipment->shipped_at?->translatedFormat('d M Y · H:i') ?? 'Belum berangkat'" />
                            @if ($shipment->notes)
                                <x-info-row label="Catatan" :value="$shipment->notes" />
                            @endif
                        </dl>
                    </article>
                @empty
                    <x-empty-state icon="truck"
                                   title="Tidak ada pengiriman berjalan"
                                   message="Semua pengiriman sudah selesai atau belum ada yang dicatat." />
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
