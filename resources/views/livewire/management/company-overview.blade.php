<div wire:poll.{{ config('phm.poll_seconds') }}s class="space-y-6">

    <x-page-header title="Ringkasan Perusahaan"
                   subtitle="Kondisi operasional hari ini, {{ today()->translatedFormat('l d F Y') }}."
                   :updated-at="$this->kpis['as_of']->translatedFormat('H:i:s')" />

    {{-- ===================== FOUR HEADLINE KPI CARDS ===================== --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Pesanan Aktif"
                     :value="number_format($this->kpis['active_orders'], 0, ',', '.')"
                     unit="pesanan"
                     icon="package"
                     hint="Belum selesai atau belum dikirim penuh."
                     :href="route('orders.index')" />

        <x-stat-card label="Produksi Dilaporkan"
                     :value="number_format($this->kpis['reported_today'], 0, ',', '.')"
                     unit="pcs"
                     icon="clipboard-list"
                     hint="Termasuk yang belum diperiksa supervisor."
                     :href="route('dashboard.production')" />

        <x-stat-card label="Produksi Terverifikasi"
                     :value="number_format($this->kpis['verified_today'], 0, ',', '.')"
                     unit="pcs"
                     icon="circle-check"
                     tone="positive"
                     hint="Sudah dikonfirmasi supervisor."
                     :href="route('dashboard.production')" />

        <x-stat-card label="Perlu Perhatian"
                     :value="number_format($this->kpis['attention_count'], 0, ',', '.')"
                     unit="hal"
                     icon="triangle-alert"
                     :tone="$this->kpis['attention_count'] > 0 ? 'critical' : 'positive'"
                     hint="Tenggat, kendala, dan pengiriman bermasalah."
                     :href="route('dashboard.alerts')" />
    </div>

    {{-- Data transparency note — not decoration, a correctness statement. --}}
    <div class="flex items-start gap-3 rounded-[var(--radius-card)] border border-line bg-surface-muted p-4">
        <x-lucide-info class="mt-0.5 size-5 shrink-0 text-interactive" aria-hidden="true" />
        <p class="text-sm leading-relaxed text-muted">
            <span class="font-semibold text-body">Dilaporkan</span> adalah jumlah yang dicatat operator,
            termasuk {{ number_format($this->kpis['pending_today'], 0, ',', '.') }} pcs
            dari {{ $this->kpis['pending_reports'] }} laporan yang belum diperiksa.
            <span class="font-semibold text-body">Terverifikasi</span> hanya mencakup laporan yang sudah
            disetujui supervisor. Angka diperbarui otomatis setiap {{ config('phm.poll_seconds') }} detik.
        </p>
    </div>

    {{-- ========================= PRODUCTION =========================== --}}
    <section aria-labelledby="judul-produksi">
        <div class="flex items-center justify-between gap-3">
            <h2 id="judul-produksi" class="phm-section-heading">Progres Produksi Hari Ini</h2>
            <a href="{{ route('dashboard.production') }}" class="text-sm font-semibold text-interactive">
                Lihat semua
            </a>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($this->progress as $row)
                <div class="phm-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-base font-semibold">{{ $row['product'] }}</p>
                            <p class="phm-helper mt-0.5">
                                {{ $row['wo'] }} · {{ $row['operator'] }}
                            </p>
                        </div>
                        <p class="text-right text-sm tabular-nums">
                            <span class="block font-semibold">
                                {{ number_format($row['verified'], 0, ',', '.') }} /
                                {{ number_format($row['target'], 0, ',', '.') }} {{ $row['unit'] }}
                            </span>
                            <span class="phm-helper">terverifikasi</span>
                        </p>
                    </div>

                    <x-progress-bar class="mt-4"
                                    :reported="$row['reported']"
                                    :verified="$row['verified']"
                                    :target="$row['target']"
                                    :unit="$row['unit']" />
                </div>
            @empty
                <x-empty-state icon="factory"
                               title="Belum ada produksi hari ini"
                               message="Belum ada penugasan produksi yang berjalan." />
            @endforelse
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- ========================= SHIPPING ========================== --}}
        <section aria-labelledby="judul-pengiriman">
            <div class="flex items-center justify-between gap-3">
                <h2 id="judul-pengiriman" class="phm-section-heading">Pengiriman</h2>
                <a href="{{ route('dashboard.shipping') }}" class="text-sm font-semibold text-interactive">
                    Detail
                </a>
            </div>

            <div class="phm-card mt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="phm-helper">Dikirim hari ini</p>
                        <p class="mt-1 text-[1.5rem] leading-none font-semibold tabular-nums">
                            {{ number_format($this->shipping['today_quantity'], 0, ',', '.') }}
                            <span class="text-sm font-normal text-muted">pcs</span>
                        </p>
                    </div>
                    <div>
                        <p class="phm-helper">Dalam perjalanan</p>
                        <p class="mt-1 text-[1.5rem] leading-none font-semibold tabular-nums">
                            {{ $this->shipping['in_transit'] }}
                            <span class="text-sm font-normal text-muted">pengiriman</span>
                        </p>
                    </div>
                </div>

                <dl class="mt-4 border-t border-line pt-3">
                    <x-info-row label="Siap dikirim" :value="$this->shipping['prepared'] . ' pengiriman'" />
                    <x-info-row label="Sudah tiba" :value="$this->shipping['arrived'] . ' pengiriman'" />
                    <x-info-row label="Sudah diterima" :value="$this->shipping['received'] . ' pengiriman'" />
                    @if ($this->shipping['issues'] > 0)
                        <x-info-row label="Bermasalah">
                            <span class="font-semibold text-rose-700 dark:text-rose-300">
                                {{ $this->shipping['issues'] }} pengiriman
                            </span>
                        </x-info-row>
                    @endif
                </dl>
            </div>
        </section>

        {{-- ==================== ORDERS NEAR DEADLINE =================== --}}
        <section aria-labelledby="judul-tenggat">
            <h2 id="judul-tenggat" class="phm-section-heading">Pesanan Mendekati Tenggat</h2>

            <div class="phm-card mt-4">
                @forelse ($this->approachingDeadline as $order)
                    <div class="flex items-start justify-between gap-4 border-b border-line py-3 first:pt-0 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate text-base font-medium">{{ $order->partner->name }}</p>
                            <p class="phm-helper mt-0.5">
                                {{ $order->order_number }} · {{ $order->product->name }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p @class([
                                'text-sm font-semibold',
                                'text-rose-700 dark:text-rose-300' => $order->isOverdue(),
                            ])>
                                {{ $order->delivery_target_date->translatedFormat('d M Y') }}
                            </p>
                            <p class="phm-helper">
                                sisa {{ number_format($order->remainingToShipQuantity(), 0, ',', '.') }} {{ $order->product->unit }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-base text-muted">
                        Tidak ada pesanan yang mendekati tenggat dalam 7 hari ke depan.
                    </p>
                @endforelse
            </div>
        </section>

        {{-- ===================== PENDING VERIFICATION ================== --}}
        <section aria-labelledby="judul-pemeriksaan">
            <h2 id="judul-pemeriksaan" class="phm-section-heading">Menunggu Verifikasi</h2>

            <div class="phm-card mt-4">
                @forelse ($this->pendingReviews as $report)
                    <div class="flex items-start justify-between gap-4 border-b border-line py-3 first:pt-0 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate text-base font-medium">{{ $report->user->full_name }}</p>
                            <p class="phm-helper mt-0.5">
                                {{ $report->assignment->productName() }} ·
                                {{ $report->submitted_at->translatedFormat('d M, H:i') }}
                            </p>
                        </div>
                        <p class="shrink-0 text-right text-sm font-semibold tabular-nums">
                            {{ number_format($report->quantity_completed, 0, ',', '.') }}
                            <span class="font-normal text-muted">{{ $report->assignment->unit() }}</span>
                        </p>
                    </div>
                @empty
                    <p class="py-4 text-center text-base text-muted">
                        Semua laporan sudah diperiksa.
                    </p>
                @endforelse
            </div>
        </section>

        {{-- ========================== ALERTS =========================== --}}
        <section aria-labelledby="judul-peringatan">
            <div class="flex items-center justify-between gap-3">
                <h2 id="judul-peringatan" class="phm-section-heading">Peringatan</h2>
                <a href="{{ route('dashboard.alerts') }}" class="text-sm font-semibold text-interactive">
                    Semua
                </a>
            </div>

            <div class="phm-card mt-4">
                @forelse ($this->alerts as $alert)
                    <div class="flex items-start gap-3 border-b border-line py-3 first:pt-0 last:border-0 last:pb-0">
                        @php
                            $tone = match ($alert['severity']) {
                                'critical' => 'text-rose-700 dark:text-rose-300',
                                'warning' => 'text-amber-700 dark:text-amber-300',
                                default => 'text-interactive',
                            };
                        @endphp
                        <x-lucide-triangle-alert class="mt-0.5 size-5 shrink-0 {{ $tone }}" aria-hidden="true" />
                        <div class="min-w-0">
                            <p class="text-base font-medium">{{ $alert['title'] }}</p>
                            <p class="phm-helper mt-0.5">{{ $alert['detail'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-base text-muted">
                        Tidak ada peringatan operasional saat ini.
                    </p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ====================== RECENT ACTIVITY ========================== --}}
    <section aria-labelledby="judul-aktivitas">
        <h2 id="judul-aktivitas" class="phm-section-heading">Aktivitas Terbaru</h2>

        <div class="phm-card mt-4">
            @forelse ($this->recentActivity as $event)
                <div class="flex items-start gap-3 border-b border-line py-3 first:pt-0 last:border-0 last:pb-0">
                    <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-brand/10 text-interactive">
                        <x-lucide-activity class="size-4" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-base leading-snug">{{ $event->summary }}</p>
                        <p class="phm-helper mt-0.5">
                            {{ $event->created_at->translatedFormat('d M Y · H:i') }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-base text-muted">Belum ada aktivitas tercatat.</p>
            @endforelse
        </div>
    </section>
</div>
