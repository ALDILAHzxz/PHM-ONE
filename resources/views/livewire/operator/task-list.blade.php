{{--
    Polling (not WebSockets) is how this screen stays current. The interval is
    server-configured and every poll re-runs the same authorized queries.
--}}
<div wire:poll.{{ config('phm.poll_seconds') }}s class="space-y-5">

    <x-page-header title="Tugas Saya"
                   subtitle="Berikut pekerjaan yang perlu Anda selesaikan hari ini."
                   :updated-at="now()->translatedFormat('H:i')" />

    {{-- Correction alerts first: the operator must act on these. --}}
    @if ($this->needsCorrection->isNotEmpty())
        <div role="alert" class="rounded-[var(--radius-card)] border border-rose-300 bg-rose-50 p-4 dark:border-rose-400/40 dark:bg-rose-400/10">
            <div class="flex items-start gap-3">
                <x-lucide-circle-alert class="mt-0.5 size-5 shrink-0 text-rose-700 dark:text-rose-300" aria-hidden="true" />
                <div class="min-w-0">
                    <p class="text-base font-semibold text-rose-900 dark:text-rose-100">
                        {{ $this->needsCorrection->count() }} laporan perlu diperbaiki
                    </p>
                    <ul class="mt-2 space-y-2">
                        @foreach ($this->needsCorrection as $report)
                            <li class="text-sm text-rose-900 dark:text-rose-100">
                                <span class="font-medium">{{ $report->report_number }}</span>
                                — {{ number_format($report->quantity_completed, 0, ',', '.') }}
                                {{ $report->assignment->unit() }}
                                @if ($report->latestReview?->review_notes)
                                    <span class="block text-rose-800/90 dark:text-rose-200/90">
                                        Catatan atasan: {{ $report->latestReview->review_notes }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-sm text-rose-800/90 dark:text-rose-200/90">
                        Catat ulang hasil yang benar menggunakan tombol di bawah.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- =====================================================================
         MAIN WORK CARDS
    ====================================================================== --}}
    @forelse ($this->assignments as $assignment)
        <section class="phm-card" aria-labelledby="tugas-{{ $assignment->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="phm-helper">{{ $assignment->woNumber() }}</p>
                    <h2 id="tugas-{{ $assignment->id }}" class="phm-section-heading mt-0.5">
                        {{ $assignment->productName() }}
                    </h2>
                    <p class="phm-helper mt-1">
                        {{ $assignment->stage?->name ?? 'Tahap produksi' }}
                        @if ($assignment->workStation)
                            · {{ $assignment->workStation->name }}
                        @endif
                        @if ($assignment->workShift)
                            · {{ $assignment->workShift->name }}
                        @endif
                    </p>
                </div>

                <span class="phm-badge bg-sky-100 text-sky-900 ring-sky-300 dark:bg-sky-400/15 dark:text-sky-200 dark:ring-sky-400/40">
                    {{ $assignment->statusLabel() }}
                </span>
            </div>

            {{-- The three numbers that matter, large and unambiguous. --}}
            <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-[var(--radius-control)] bg-surface-muted p-3 ring-1 ring-line">
                    <p class="text-[0.8125rem] text-muted">Target</p>
                    <p class="mt-1 text-[1.375rem] leading-none font-semibold tabular-nums md:text-[1.625rem]">
                        {{ number_format($assignment->targetQty(), 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-[0.75rem] text-muted">{{ $assignment->unit() }}</p>
                </div>
                <div class="rounded-[var(--radius-control)] bg-surface-muted p-3 ring-1 ring-line">
                    <p class="text-[0.8125rem] text-muted">Sudah dilaporkan</p>
                    <p class="mt-1 text-[1.375rem] leading-none font-semibold tabular-nums text-interactive md:text-[1.625rem]">
                        {{ number_format($assignment->reportedQty(), 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-[0.75rem] text-muted">{{ $assignment->unit() }}</p>
                </div>
                <div class="rounded-[var(--radius-control)] bg-surface-muted p-3 ring-1 ring-line">
                    <p class="text-[0.8125rem] text-muted">Sisa pekerjaan</p>
                    <p class="mt-1 text-[1.375rem] leading-none font-semibold tabular-nums md:text-[1.625rem]">
                        {{ number_format($assignment->remainingQty(), 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-[0.75rem] text-muted">{{ $assignment->unit() }}</p>
                </div>
            </div>

            <x-progress-bar class="mt-5"
                            :reported="$assignment->reportedQty()"
                            :verified="$assignment->verifiedQty()"
                            :target="$assignment->targetQty()"
                            :unit="$assignment->unit()" />

            @if ($assignment->pendingQty() > 0)
                <p class="mt-3 flex items-center gap-2 text-sm text-amber-800 dark:text-amber-200">
                    <x-lucide-clock class="size-4 shrink-0" aria-hidden="true" />
                    {{ number_format($assignment->pendingQty(), 0, ',', '.') }} {{ $assignment->unit() }}
                    menunggu pemeriksaan atasan.
                </p>
            @endif

            {{-- THE primary action — nothing competing beside it. --}}
            <a href="{{ route('operator.report.create', $assignment) }}"
               class="phm-btn phm-btn-primary mt-5 w-full text-[1.125rem]">
                <x-lucide-square-pen class="size-5" aria-hidden="true" />
                Catat Hasil Kerja
            </a>
        </section>
    @empty
        <x-empty-state icon="clipboard-list"
                       title="Belum ada tugas hari ini"
                       message="Anda tidak memiliki penugasan produksi yang aktif saat ini. Hubungi atasan atau PPIC bila seharusnya ada pekerjaan." />
    @endforelse

    {{-- =====================================================================
         SECONDARY — today's own totals, latest reports, upcoming work.
    ====================================================================== --}}
    @if ($this->assignments->isNotEmpty() || $this->latestReports->isNotEmpty())
        <section class="phm-card" aria-labelledby="ringkasan-hari-ini">
            <h2 id="ringkasan-hari-ini" class="phm-card-heading">Hasil Saya Hari Ini</h2>
            <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                <div>
                    <p class="text-[0.8125rem] text-muted">Dilaporkan</p>
                    <p class="mt-1 text-[1.5rem] leading-none font-semibold tabular-nums">
                        {{ number_format($this->todayTotals['reported'], 0, ',', '.') }}
                    </p>
                </div>
                <div>
                    <p class="text-[0.8125rem] text-muted">Terverifikasi</p>
                    <p class="mt-1 text-[1.5rem] leading-none font-semibold tabular-nums text-emerald-700 dark:text-emerald-300">
                        {{ number_format($this->todayTotals['verified'], 0, ',', '.') }}
                    </p>
                </div>
                <div>
                    <p class="text-[0.8125rem] text-muted">Menunggu</p>
                    <p class="mt-1 text-[1.5rem] leading-none font-semibold tabular-nums text-amber-700 dark:text-amber-300">
                        {{ number_format($this->todayTotals['pending'], 0, ',', '.') }}
                    </p>
                </div>
            </div>
            <p class="phm-helper mt-3 text-center">
                Angka "Terverifikasi" hanya bertambah setelah atasan memeriksa laporan Anda.
            </p>
        </section>
    @endif

    @if ($this->latestReports->isNotEmpty())
        <section class="phm-card" aria-labelledby="laporan-terakhir">
            <div class="flex items-center justify-between gap-3">
                <h2 id="laporan-terakhir" class="phm-card-heading">Laporan Terakhir Saya</h2>
                <a href="{{ route('operator.history') }}" class="text-sm font-semibold text-interactive">
                    Lihat semua
                </a>
            </div>

            <ul class="mt-4 divide-y divide-line">
                @foreach ($this->latestReports as $report)
                    <li class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="text-base font-medium">
                                {{ number_format($report->quantity_completed, 0, ',', '.') }}
                                {{ $report->assignment->unit() }}
                                <span class="font-normal text-muted">· {{ $report->assignment->productName() }}</span>
                            </p>
                            <p class="phm-helper mt-0.5">
                                {{ $report->submitted_at->translatedFormat('d M, H:i') }}
                                @if ($report->has_issue)
                                    · <span class="text-amber-700 dark:text-amber-300">{{ $report->issueLabel() }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="phm-badge shrink-0 {{ $report->statusBadgeClasses() }}">
                            {{ $report->statusLabel() }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($this->upcoming->isNotEmpty())
        <section class="phm-card" aria-labelledby="tugas-berikutnya">
            <h2 id="tugas-berikutnya" class="phm-card-heading">Pekerjaan Berikutnya</h2>
            <ul class="mt-4 divide-y divide-line">
                @foreach ($this->upcoming as $next)
                    <li class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate text-base font-medium">{{ $next->productName() }}</p>
                            <p class="phm-helper">{{ $next->woNumber() }}</p>
                        </div>
                        <p class="shrink-0 text-right">
                            <span class="block text-sm font-medium">
                                {{ number_format($next->targetQty(), 0, ',', '.') }} {{ $next->unit() }}
                            </span>
                            <span class="phm-helper">
                                {{ $next->planned_date->translatedFormat('d M Y') }}
                            </span>
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
