<x-app-layout title="Kendala Produksi" page-title="Kendala Produksi">
    <div class="space-y-5">
        <x-page-header title="Kendala Produksi"
                       subtitle="Masalah yang dilaporkan operator saat mencatat hasil kerja." />

        @forelse ($issues as $report)
            <article class="phm-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="phm-badge bg-amber-100 text-amber-900 ring-amber-300 dark:bg-amber-400/15 dark:text-amber-200 dark:ring-amber-400/40">
                            <x-lucide-triangle-alert class="size-3.5" aria-hidden="true" />
                            {{ $report->issueLabel() }}
                        </span>
                        <h2 class="phm-card-heading mt-2">{{ $report->user->full_name }}</h2>
                        <p class="phm-helper mt-1">
                            {{ $report->assignment->productName() }} · {{ $report->assignment->woNumber() }}
                            @if ($report->assignment->stage)
                                · {{ $report->assignment->stage->name }}
                            @endif
                        </p>
                    </div>
                    <span class="phm-badge shrink-0 {{ $report->statusBadgeClasses() }}">
                        {{ $report->statusLabel() }}
                    </span>
                </div>

                <p class="mt-4 rounded-[var(--radius-control)] bg-surface-muted p-4 text-base leading-relaxed ring-1 ring-line">
                    {{ $report->issue_description ?? 'Operator tidak menuliskan penjelasan.' }}
                </p>

                <dl class="mt-4">
                    <x-info-row label="Waktu laporan"
                                :value="$report->submitted_at->translatedFormat('l, d F Y · H:i')" />
                    <x-info-row label="Jumlah pada laporan ini"
                                :value="number_format($report->quantity_completed, 0, ',', '.') . ' ' . $report->assignment->unit()" />
                    <x-info-row label="Nomor laporan" :value="$report->report_number" />
                </dl>
            </article>
        @empty
            <x-empty-state icon="circle-check"
                           title="Tidak ada kendala tercatat"
                           message="Belum ada operator yang melaporkan kendala pada pekerjaannya." />
        @endforelse

        @if ($issues->hasPages())
            <div class="phm-card py-4">{{ $issues->links() }}</div>
        @endif
    </div>
</x-app-layout>
