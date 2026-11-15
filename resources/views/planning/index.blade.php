<x-app-layout title="Rencana Produksi" page-title="Perencanaan">
    <div class="space-y-6">
        <x-page-header title="Rencana Produksi"
                       subtitle="Hubungkan pesanan mitra dengan pekerjaan di lantai produksi."
                       :updated-at="now()->translatedFormat('H:i')" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Pesanan perlu direncanakan"
                         :value="$ordersNeedingPlanning->count()"
                         unit="pesanan" icon="package"
                         :tone="$ordersNeedingPlanning->count() > 0 ? 'warning' : 'positive'"
                         hint="Jumlah work order masih kurang dari pesanan." />
            <x-stat-card label="Work order terbuka"
                         :value="$openWorkOrders->count()"
                         unit="WO" icon="factory"
                         :href="route('planning.work-orders')" />
            <x-stat-card label="Penugasan hari ini"
                         :value="$todayAssignments->count()"
                         unit="tugas" icon="users"
                         :href="route('planning.assignments')" />
            <x-stat-card label="Terverifikasi hari ini"
                         :value="number_format($production['verified'], 0, ',', '.')"
                         unit="pcs" icon="circle-check" tone="positive"
                         :hint="'Dilaporkan ' . number_format($production['reported'], 0, ',', '.') . ' pcs'" />
        </div>

        <section aria-labelledby="judul-perlu-rencana">
            <h2 id="judul-perlu-rencana" class="phm-section-heading">Pesanan Yang Perlu Direncanakan</h2>
            <p class="phm-helper mt-1">
                Pesanan ini belum punya work order untuk seluruh jumlahnya.
            </p>

            <div class="phm-card mt-4">
                @forelse ($ordersNeedingPlanning as $order)
                    @php
                        $planned = (int) $order->workOrders()->sum('target_quantity');
                        $unplanned = max(0, $order->quantity - $planned);
                    @endphp
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line py-3 first:pt-0 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="text-base font-medium">{{ $order->partner->name }}</p>
                            <p class="phm-helper mt-0.5">
                                {{ $order->order_number }} · {{ $order->product->name }} ·
                                target kirim {{ $order->delivery_target_date->translatedFormat('d M Y') }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold tabular-nums">
                                belum direncanakan {{ number_format($unplanned, 0, ',', '.') }} {{ $order->product->unit }}
                            </p>
                            <p class="phm-helper">
                                dari {{ number_format($order->quantity, 0, ',', '.') }} dipesan
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-base text-muted">
                        Semua pesanan aktif sudah direncanakan produksinya.
                    </p>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="judul-target-harian">
            <div class="flex items-center justify-between gap-3">
                <h2 id="judul-target-harian" class="phm-section-heading">Target Harian Hari Ini</h2>
                <a href="{{ route('planning.assignments') }}" class="text-sm font-semibold text-interactive">
                    Kelola penugasan
                </a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($todayAssignments as $assignment)
                    <div class="phm-card">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-base font-semibold">{{ $assignment->user->full_name }}</p>
                                <p class="phm-helper mt-0.5">
                                    {{ $assignment->productName() }} · {{ $assignment->woNumber() }}
                                </p>
                            </div>
                            <p class="text-right text-sm tabular-nums">
                                <span class="block font-semibold">
                                    {{ number_format($assignment->reportedQty(), 0, ',', '.') }} /
                                    {{ number_format($assignment->targetQty(), 0, ',', '.') }} {{ $assignment->unit() }}
                                </span>
                                <span class="phm-helper">dilaporkan</span>
                            </p>
                        </div>
                        <x-progress-bar class="mt-4"
                                        :reported="$assignment->reportedQty()"
                                        :verified="$assignment->verifiedQty()"
                                        :target="$assignment->targetQty()"
                                        :unit="$assignment->unit()" />
                    </div>
                @empty
                    <x-empty-state icon="users"
                                   title="Belum ada penugasan hari ini"
                                   message="Buat penugasan agar operator melihat pekerjaannya." />
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
