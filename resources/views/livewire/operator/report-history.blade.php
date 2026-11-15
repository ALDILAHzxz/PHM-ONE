<div class="space-y-5">
    <x-page-header title="Riwayat Saya"
                   subtitle="Semua hasil pekerjaan yang pernah Anda catat." />

    {{-- Filter chips — large enough for a gloved thumb. --}}
    <div class="flex flex-wrap gap-2" role="group" aria-label="Filter status laporan">
        @foreach ($statusOptions as $value => $label)
            <button type="button"
                    wire:click="$set('statusFilter', '{{ $value }}')"
                    @class([
                        'min-h-11 rounded-full border px-4 text-sm font-medium transition-colors duration-150',
                        'border-brand bg-brand text-white' => $statusFilter === $value,
                        'border-line-strong bg-surface text-body hover:border-brand' => $statusFilter !== $value,
                    ])
                    @if ($statusFilter === $value) aria-current="true" @endif>
                {{ $label }}
            </button>
        @endforeach
    </div>

    @forelse ($reports as $report)
        <article class="phm-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="phm-helper">{{ $report->report_number }}</p>
                    <h2 class="phm-card-heading mt-0.5">
                        {{ number_format($report->quantity_completed, 0, ',', '.') }}
                        {{ $report->assignment->unit() }}
                    </h2>
                    <p class="phm-helper mt-1">
                        {{ $report->assignment->productName() }} · {{ $report->assignment->woNumber() }}
                    </p>
                </div>
                <span class="phm-badge shrink-0 {{ $report->statusBadgeClasses() }}">
                    {{ $report->statusLabel() }}
                </span>
            </div>

            <dl class="mt-4">
                <x-info-row label="Waktu pencatatan"
                            :value="$report->submitted_at->translatedFormat('l, d F Y · H:i')" />
                <x-info-row label="Kendala" :value="$report->issueLabel()" />
                @if ($report->has_issue && $report->issue_description)
                    <x-info-row label="Penjelasan" :value="$report->issue_description" />
                @endif
                @if ($report->additional_notes)
                    <x-info-row label="Keterangan" :value="$report->additional_notes" />
                @endif
                @if ($report->latestReview)
                    <x-info-row label="Diperiksa oleh"
                                :value="($report->latestReview->reviewer?->full_name ?? 'Atasan') . ' · ' . $report->latestReview->reviewed_at->translatedFormat('d M Y, H:i')" />
                    @if ($report->latestReview->review_notes)
                        <x-info-row label="Catatan pemeriksaan" :value="$report->latestReview->review_notes" />
                    @endif
                @endif
            </dl>

            @if ($report->status === \App\Support\ReportStatus::NEEDS_CORRECTION)
                <a href="{{ route('operator.report.create', $report->assignment) }}"
                   class="phm-btn phm-btn-primary mt-4 w-full">
                    Catat Ulang Hasil
                </a>
            @endif
        </article>
    @empty
        <x-empty-state icon="history"
                       title="Belum ada riwayat"
                       :message="$statusFilter === 'semua'
                           ? 'Anda belum pernah mencatat hasil pekerjaan.'
                           : 'Tidak ada laporan dengan status ini.'" />
    @endforelse

    @if ($reports->hasPages())
        <div class="phm-card py-4">
            {{ $reports->links() }}
        </div>
    @endif
</div>
