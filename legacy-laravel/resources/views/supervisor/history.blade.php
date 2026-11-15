<x-app-layout title="Riwayat Tim" page-title="Riwayat Tim">
    <div class="space-y-5">
        <x-page-header title="Riwayat Tim"
                       subtitle="Seluruh laporan produksi tim beserta hasil pemeriksaannya." />

        @forelse ($reports as $report)
            <article class="phm-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="phm-helper">{{ $report->report_number }}</p>
                        <h2 class="phm-card-heading mt-0.5">
                            {{ $report->user->full_name }}
                            <span class="font-normal text-muted">
                                · {{ number_format($report->quantity_completed, 0, ',', '.') }}
                                {{ $report->assignment->unit() }}
                            </span>
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
                    <x-info-row label="Dicatat"
                                :value="$report->submitted_at->translatedFormat('d M Y · H:i')" />
                    <x-info-row label="Kendala" :value="$report->issueLabel()" />
                    @if ($report->latestReview)
                        <x-info-row label="Diperiksa oleh"
                                    :value="($report->latestReview->reviewer?->full_name ?? '—') . ' · ' . $report->latestReview->reviewed_at->translatedFormat('d M Y · H:i')" />
                        @if ($report->latestReview->review_notes)
                            <x-info-row label="Catatan" :value="$report->latestReview->review_notes" />
                        @endif
                    @else
                        <x-info-row label="Pemeriksaan" value="Belum diperiksa" />
                    @endif
                </dl>
            </article>
        @empty
            <x-empty-state icon="history"
                           title="Belum ada riwayat"
                           message="Tim Anda belum mencatat hasil produksi." />
        @endforelse

        @if ($reports->hasPages())
            <div class="phm-card py-4">{{ $reports->links() }}</div>
        @endif
    </div>
</x-app-layout>
