<x-app-layout title="Produksi" page-title="Produksi">
    <div class="space-y-6">
        <x-page-header title="Produksi"
                       subtitle="Perbandingan hasil yang dilaporkan dan yang sudah diverifikasi."
                       :updated-at="now()->translatedFormat('H:i')" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Dilaporkan hari ini"
                         :value="number_format($today['reported'], 0, ',', '.')"
                         unit="pcs" icon="clipboard-list"
                         :hint="$today['reports'] . ' laporan masuk'" />
            <x-stat-card label="Terverifikasi hari ini"
                         :value="number_format($today['verified'], 0, ',', '.')"
                         unit="pcs" icon="circle-check" tone="positive"
                         hint="Disetujui supervisor." />
            <x-stat-card label="Menunggu pemeriksaan"
                         :value="number_format($today['pending'], 0, ',', '.')"
                         unit="pcs" icon="clock"
                         :tone="$today['pending'] > 0 ? 'warning' : 'default'"
                         :hint="$today['pending_reports'] . ' laporan'" />
            <x-stat-card label="Dilaporkan kemarin"
                         :value="number_format($yesterday['reported'], 0, ',', '.')"
                         unit="pcs" icon="history"
                         :hint="'Terverifikasi ' . number_format($yesterday['verified'], 0, ',', '.') . ' pcs'" />
        </div>

        <section aria-labelledby="judul-progres">
            <h2 id="judul-progres" class="phm-section-heading">Progres per Penugasan</h2>
            <p class="phm-helper mt-1">
                Batang biru tua menunjukkan jumlah terverifikasi, biru muda menunjukkan
                jumlah yang dilaporkan tetapi belum diperiksa.
            </p>

            <div class="mt-4 space-y-3">
                @forelse ($progress as $row)
                    <div class="phm-card">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-base font-semibold">{{ $row['product'] }}</p>
                                <p class="phm-helper mt-0.5">{{ $row['wo'] }} · {{ $row['operator'] }}</p>
                            </div>
                            <p class="text-right text-sm tabular-nums">
                                <span class="block font-semibold">
                                    sisa {{ number_format($row['remaining'], 0, ',', '.') }} {{ $row['unit'] }}
                                </span>
                                <span class="phm-helper">
                                    target {{ number_format($row['target'], 0, ',', '.') }}
                                </span>
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
                                   title="Belum ada penugasan aktif"
                                   message="Tidak ada penugasan produksi yang berjalan saat ini." />
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
