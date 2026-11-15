<x-app-layout title="Pesanan Mitra" page-title="Pesanan Mitra">
    <div class="space-y-5">
        <x-page-header title="Pesanan Mitra"
                       subtitle="Daftar pesanan dari mitra PHM, diurutkan dari tenggat terdekat.">
            <x-slot:actions>
                @can(\App\Support\Permissions::ORDER_CREATE)
                    <a href="{{ route('orders.create') }}" class="phm-btn phm-btn-primary">
                        <x-lucide-plus class="size-5" aria-hidden="true" />
                        Tambah Pesanan
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>

        {{-- Search + status filter --}}
        <form method="GET" action="{{ route('orders.index') }}" class="phm-card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="cari" class="phm-label">Cari pesanan</label>
                    <div class="relative">
                        <input id="cari" name="cari" type="search" value="{{ $search }}"
                               class="phm-input pl-12"
                               placeholder="Nomor pesanan, nomor PO, mitra, atau barang">
                        <x-lucide-search class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted"
                                         aria-hidden="true" />
                    </div>
                </div>

                <div class="sm:w-56">
                    <label for="status" class="phm-label">Status</label>
                    <select id="status" name="status" class="phm-input">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="phm-btn phm-btn-secondary sm:w-32">Terapkan</button>
            </div>
        </form>

        @forelse ($orders as $order)
            <a href="{{ route('orders.show', $order) }}" class="phm-card phm-card-interactive block">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="phm-helper">{{ $order->order_number }} · PO {{ $order->po_number }}</p>
                        <h2 class="phm-card-heading mt-0.5">{{ $order->partner->name }}</h2>
                        <p class="phm-helper mt-1">{{ $order->product->name }}</p>
                    </div>
                    <span class="phm-badge shrink-0 {{ $order->statusBadgeClasses() }}">
                        {{ $order->statusLabel() }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <p class="phm-helper">Jumlah</p>
                        <p class="mt-0.5 text-base font-semibold tabular-nums">
                            {{ number_format($order->quantity, 0, ',', '.') }} {{ $order->product->unit }}
                        </p>
                    </div>
                    <div>
                        <p class="phm-helper">Sudah dikirim</p>
                        <p class="mt-0.5 text-base font-semibold tabular-nums">
                            {{ number_format($order->shippedQuantity(), 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="phm-helper">Sisa kirim</p>
                        <p class="mt-0.5 text-base font-semibold tabular-nums">
                            {{ number_format($order->remainingToShipQuantity(), 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="phm-helper">Target kirim</p>
                        <p @class([
                            'mt-0.5 text-base font-semibold',
                            'text-rose-700 dark:text-rose-300' => $order->isOverdue(),
                        ])>
                            {{ $order->delivery_target_date->translatedFormat('d M Y') }}
                        </p>
                    </div>
                </div>

                @if ($order->isOverdue())
                    <p class="mt-3 flex items-center gap-2 text-sm font-medium text-rose-700 dark:text-rose-300">
                        <x-lucide-triangle-alert class="size-4 shrink-0" aria-hidden="true" />
                        Melewati target pengiriman.
                    </p>
                @endif
            </a>
        @empty
            <x-empty-state icon="package"
                           title="Tidak ada pesanan"
                           :message="$search || $status
                               ? 'Tidak ada pesanan yang cocok dengan pencarian Anda.'
                               : 'Belum ada pesanan mitra yang tercatat.'" />
        @endforelse

        @if ($orders->hasPages())
            <div class="phm-card py-4">{{ $orders->links() }}</div>
        @endif
    </div>
</x-app-layout>
