<x-app-layout :title="$order->order_number" page-title="Detail Pesanan">
    <div class="mx-auto w-full max-w-[52rem] space-y-5">

        <a href="{{ route('orders.index') }}" class="phm-btn phm-btn-ghost -ml-3 min-h-11 px-3 text-base">
            <x-lucide-arrow-left class="size-5" aria-hidden="true" />
            Kembali ke daftar pesanan
        </a>

        <div class="phm-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="phm-helper">{{ $order->order_number }}</p>
                    <h1 class="phm-page-title mt-0.5">{{ $order->partner->name }}</h1>
                    <p class="mt-1 text-base text-muted">PO {{ $order->po_number }}</p>
                </div>
                <span class="phm-badge shrink-0 {{ $order->statusBadgeClasses() }}">
                    {{ $order->statusLabel() }}
                </span>
            </div>

            <x-progress-bar class="mt-5"
                            :reported="min($order->quantity, $order->verifiedProducedQuantity())"
                            :verified="$order->shippedQuantity()"
                            :target="$order->quantity"
                            :unit="$order->product->unit"
                            :show-legend="false" />
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[0.8125rem] text-muted">
                <span class="flex items-center gap-1.5">
                    <span class="size-2.5 shrink-0 rounded-full bg-brand" aria-hidden="true"></span>
                    Sudah dikirim {{ number_format($order->shippedQuantity(), 0, ',', '.') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="size-2.5 shrink-0 rounded-full bg-sky-300 dark:bg-sky-400/50" aria-hidden="true"></span>
                    Produksi terverifikasi {{ number_format($order->verifiedProducedQuantity(), 0, ',', '.') }}
                </span>
                <span>Dipesan {{ number_format($order->quantity, 0, ',', '.') }} {{ $order->product->unit }}</span>
            </div>
        </div>

        <section class="phm-card">
            <h2 class="phm-card-heading">Informasi Pesanan</h2>
            <dl class="mt-3">
                <x-info-row label="Barang" :value="$order->product->name" />
                <x-info-row label="Kode barang" :value="$order->product->sku" />
                <x-info-row label="Jumlah pesanan"
                            :value="number_format($order->quantity, 0, ',', '.') . ' ' . $order->product->unit"
                            strong />
                <x-info-row label="Target pengiriman"
                            :value="$order->delivery_target_date->translatedFormat('l, d F Y')" />
                <x-info-row label="Siap dikirim"
                            :value="number_format($order->readyToShipQuantity(), 0, ',', '.') . ' ' . $order->product->unit" />
                <x-info-row label="Sisa kirim"
                            :value="number_format($order->remainingToShipQuantity(), 0, ',', '.') . ' ' . $order->product->unit" />
                <x-info-row label="Dicatat oleh"
                            :value="($order->creator?->full_name ?? '—') . ' · ' . $order->created_at->translatedFormat('d M Y · H:i')" />
                @if ($order->notes)
                    <x-info-row label="Catatan" :value="$order->notes" />
                @endif
            </dl>

            <div class="mt-4 rounded-[var(--radius-control)] bg-surface-muted p-4 ring-1 ring-line">
                <p class="phm-helper">
                    Kontak mitra:
                    <span class="font-medium text-body">{{ $order->partner->contact_person ?? '—' }}</span>
                    @if ($order->partner->phone) · {{ $order->partner->phone }} @endif
                </p>
                @if ($order->partner->address)
                    <p class="phm-helper mt-0.5">{{ $order->partner->address }}</p>
                @endif
            </div>
        </section>

        <section class="phm-card">
            <h2 class="phm-card-heading">Work Order</h2>
            @forelse ($order->workOrders as $wo)
                <div class="flex items-start justify-between gap-4 border-b border-line py-3 first:pt-3 last:border-0 last:pb-0">
                    <div class="min-w-0">
                        <p class="text-base font-medium">{{ $wo->wo_number }}</p>
                        <p class="phm-helper mt-0.5">
                            {{ $wo->product->name }} · rencana {{ $wo->planned_date->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-semibold tabular-nums">
                            {{ number_format($wo->verifiedQuantity(), 0, ',', '.') }} /
                            {{ number_format($wo->target_quantity, 0, ',', '.') }}
                        </p>
                        <p class="phm-helper">terverifikasi · {{ $wo->statusLabel() }}</p>
                    </div>
                </div>
            @empty
                <p class="mt-3 text-base text-muted">
                    Belum ada work order. PPIC akan membuat rencana produksi untuk pesanan ini.
                </p>
            @endforelse
        </section>

        <section class="phm-card">
            <h2 class="phm-card-heading">Pengiriman</h2>
            @forelse ($order->shipments as $shipment)
                <div class="flex items-start justify-between gap-4 border-b border-line py-3 first:pt-3 last:border-0 last:pb-0">
                    <div class="min-w-0">
                        <p class="text-base font-medium">{{ $shipment->shipment_number }}</p>
                        <p class="phm-helper mt-0.5">
                            {{ number_format($shipment->quantity_shipped, 0, ',', '.') }} {{ $order->product->unit }}
                            @if ($shipment->driver) · {{ $shipment->driver->full_name }} @endif
                            @if ($shipment->shipped_at) · {{ $shipment->shipped_at->translatedFormat('d M Y') }} @endif
                        </p>
                    </div>
                    <span class="phm-badge shrink-0 {{ $shipment->statusBadgeClasses() }}">
                        {{ $shipment->statusLabel() }}
                    </span>
                </div>
            @empty
                <p class="mt-3 text-base text-muted">Belum ada pengiriman untuk pesanan ini.</p>
            @endforelse
        </section>
    </div>
</x-app-layout>
