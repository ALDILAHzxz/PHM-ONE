<div wire:poll.{{ config('phm.poll_seconds') }}s class="space-y-5">

    <x-page-header title="Pemeriksaan Hari Ini"
                   subtitle="Periksa laporan produksi tim Anda sebelum dihitung sebagai hasil resmi."
                   :updated-at="now()->translatedFormat('H:i')" />

    {{-- Team totals: reported vs verified kept visibly separate. --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Dilaporkan hari ini"
                     :value="number_format($this->teamTotals['reported'], 0, ',', '.')"
                     unit="pcs"
                     icon="clipboard-list"
                     hint="Termasuk yang belum diperiksa." />
        <x-stat-card label="Terverifikasi hari ini"
                     :value="number_format($this->teamTotals['verified'], 0, ',', '.')"
                     unit="pcs"
                     icon="circle-check"
                     tone="positive"
                     hint="Sudah Anda setujui." />
        <x-stat-card label="Menunggu pemeriksaan"
                     :value="number_format($this->teamTotals['pending'], 0, ',', '.')"
                     unit="pcs"
                     icon="clock"
                     :tone="$this->teamTotals['pending'] > 0 ? 'warning' : 'default'"
                     :hint="$this->teamTotals['pending_reports'] . ' laporan'" />
    </div>

    {{-- ===================== MENUNGGU PEMERIKSAAN ======================= --}}
    <section aria-labelledby="judul-menunggu" class="space-y-4">
        <h2 id="judul-menunggu" class="phm-section-heading">
            Menunggu Pemeriksaan
            @if ($this->pending->isNotEmpty())
                <span class="ml-1 text-base font-normal text-muted">({{ $this->pending->count() }})</span>
            @endif
        </h2>

        @forelse ($this->pending as $report)
            <article class="phm-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="phm-helper">{{ $report->report_number }}</p>
                        <h3 class="phm-card-heading mt-0.5">{{ $report->user->full_name }}</h3>
                        <p class="phm-helper mt-1">
                            {{ $report->assignment->productName() }} · {{ $report->assignment->woNumber() }}
                            @if ($report->assignment->stage)
                                · {{ $report->assignment->stage->name }}
                            @endif
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-[1.5rem] leading-none font-semibold tabular-nums">
                            {{ number_format($report->quantity_completed, 0, ',', '.') }}
                        </p>
                        <p class="phm-helper">{{ $report->assignment->unit() }}</p>
                    </div>
                </div>

                <dl class="mt-4">
                    <x-info-row label="Waktu pencatatan"
                                :value="$report->submitted_at->translatedFormat('d M Y · H:i')" />
                    <x-info-row label="Kendala" :value="$report->issueLabel()" />
                    @if ($report->has_issue && $report->issue_description)
                        <x-info-row label="Penjelasan kendala" :value="$report->issue_description" />
                    @endif
                    @if ($report->additional_notes)
                        <x-info-row label="Keterangan" :value="$report->additional_notes" />
                    @endif
                    <x-info-row label="Progres penugasan">
                        {{ number_format($report->assignment->reportedQty(), 0, ',', '.') }} /
                        {{ number_format($report->assignment->targetQty(), 0, ',', '.') }}
                        {{ $report->assignment->unit() }}
                    </x-info-row>
                </dl>

                @if ($report->is_exception)
                    <div class="mt-3 flex items-start gap-3 rounded-[var(--radius-control)] border border-amber-300 bg-amber-50 p-3 text-amber-900 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-100">
                        <x-lucide-triangle-alert class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
                        <p class="text-sm">
                            Laporan ini melebihi sisa target saat dicatat dan ditandai sebagai pengecualian.
                            Periksa jumlahnya dengan teliti.
                        </p>
                    </div>
                @endif

                {{-- Correction notes box, only for the report being sent back. --}}
                @if ($correctionFor === $report->id)
                    <div class="mt-4 rounded-[var(--radius-control)] border border-rose-300 bg-rose-50/60 p-4 dark:border-rose-400/40 dark:bg-rose-400/10">
                        <label for="notes-{{ $report->id }}" class="phm-label">
                            Apa yang perlu diperbaiki?
                        </label>
                        <textarea id="notes-{{ $report->id }}"
                                  wire:model="correctionNotes"
                                  rows="3"
                                  maxlength="1000"
                                  class="phm-textarea"
                                  placeholder="Contoh: Jumlah tidak sesuai hasil hitung ulang di stasiun. Mohon dicatat ulang."></textarea>

                        @error('correctionNotes')
                            <p role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                                <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>{{ $message }}</span>
                            </p>
                        @enderror

                        <div class="mt-3 flex flex-col gap-2 sm:flex-row-reverse">
                            <button type="button"
                                    wire:click="requestCorrection"
                                    wire:loading.attr="disabled"
                                    class="phm-btn phm-btn-danger flex-1">
                                Kirim Permintaan Perbaikan
                            </button>
                            <button type="button"
                                    wire:click="cancelCorrection"
                                    class="phm-btn phm-btn-secondary sm:w-32">
                                Batal
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <button type="button"
                                wire:click="verify({{ $report->id }})"
                                wire:loading.attr="disabled"
                                wire:target="verify({{ $report->id }})"
                                class="phm-btn phm-btn-primary flex-1">
                            <span wire:loading.remove wire:target="verify({{ $report->id }})"
                                  class="flex items-center gap-2">
                                <x-lucide-circle-check class="size-5" aria-hidden="true" />
                                Verifikasi
                            </span>
                            <span wire:loading wire:target="verify({{ $report->id }})"
                                  class="flex items-center gap-2">
                                <x-lucide-loader-circle class="size-5 animate-spin" aria-hidden="true" />
                                Menyimpan…
                            </span>
                        </button>
                        <button type="button"
                                wire:click="startCorrection({{ $report->id }})"
                                class="phm-btn phm-btn-secondary sm:w-52">
                            Perlu Perbaikan
                        </button>
                    </div>
                @endif
            </article>
        @empty
            <x-empty-state icon="circle-check"
                           title="Semua laporan sudah diperiksa"
                           message="Tidak ada laporan yang menunggu pemeriksaan saat ini. Halaman ini akan memperbarui sendiri bila ada laporan baru." />
        @endforelse
    </section>

    {{-- ======================= PERLU PERBAIKAN ========================== --}}
    @if ($this->needsCorrection->isNotEmpty())
        <section aria-labelledby="judul-perbaikan">
            <h2 id="judul-perbaikan" class="phm-section-heading">
                Menunggu Dicatat Ulang
                <span class="ml-1 text-base font-normal text-muted">({{ $this->needsCorrection->count() }})</span>
            </h2>
            <p class="phm-helper mt-1">
                Laporan ini tidak dihitung ke total dilaporkan, tetapi riwayatnya tetap tersimpan.
            </p>

            <div class="mt-4 phm-card">
                <ul class="divide-y divide-line">
                    @foreach ($this->needsCorrection as $report)
                        <li class="py-3 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-base font-medium">
                                        {{ $report->user->full_name }}
                                        <span class="font-normal text-muted">
                                            · {{ number_format($report->quantity_completed, 0, ',', '.') }}
                                            {{ $report->assignment->unit() }}
                                        </span>
                                    </p>
                                    <p class="phm-helper mt-0.5">
                                        {{ $report->report_number }} ·
                                        {{ $report->assignment->productName() }}
                                    </p>
                                    @if ($report->latestReview?->review_notes)
                                        <p class="mt-1 text-sm text-rose-700 dark:text-rose-300">
                                            Catatan: {{ $report->latestReview->review_notes }}
                                        </p>
                                    @endif
                                </div>
                                <span class="phm-badge shrink-0 {{ $report->statusBadgeClasses() }}">
                                    {{ $report->statusLabel() }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ===================== BARU TERVERIFIKASI ========================= --}}
    @if ($this->recentlyVerified->isNotEmpty())
        <section aria-labelledby="judul-terverifikasi">
            <h2 id="judul-terverifikasi" class="phm-section-heading">Baru Diverifikasi Hari Ini</h2>

            <div class="mt-4 phm-card">
                <ul class="divide-y divide-line">
                    @foreach ($this->recentlyVerified as $report)
                        <li class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="text-base font-medium">
                                    {{ $report->user->full_name }}
                                    <span class="font-normal text-muted">
                                        · {{ number_format($report->quantity_completed, 0, ',', '.') }}
                                        {{ $report->assignment->unit() }}
                                    </span>
                                </p>
                                <p class="phm-helper mt-0.5">
                                    {{ $report->assignment->productName() }} ·
                                    diperiksa {{ $report->latestReview?->reviewed_at->translatedFormat('H:i') }}
                                </p>
                            </div>
                            <span class="phm-badge shrink-0 {{ $report->statusBadgeClasses() }}">
                                {{ $report->statusLabel() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</div>
