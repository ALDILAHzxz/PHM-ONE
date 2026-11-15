<?php

namespace App\Livewire\Supervisor;

use App\Exceptions\ProductionReportException;
use App\Models\ProductionReport;
use App\Services\OperationsMetrics;
use App\Services\ProductionReportService;
use App\Support\ReportStatus;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Pemeriksaan Hari Ini — the supervisor verification workspace.
 *
 * Verifying commits through ProductionReportService, which writes the review
 * record, flips the report status and logs the audit event in one transaction.
 * Verified totals therefore only move after a real approval.
 */
class ReviewQueue extends Component
{
    /** Report currently being sent back, so the notes box has a target. */
    public ?int $correctionFor = null;

    public string $correctionNotes = '';

    // ------------------------------------------------------------------- reads

    /** @return Collection<int, ProductionReport> */
    #[Computed]
    public function pending(): Collection
    {
        return ProductionReport::query()
            ->with(['user', 'assignment.workOrder.product', 'assignment.stage'])
            ->pending()
            ->oldest('submitted_at')
            ->get();
    }

    /** @return Collection<int, ProductionReport> */
    #[Computed]
    public function needsCorrection(): Collection
    {
        return ProductionReport::query()
            ->with(['user', 'assignment.workOrder.product', 'latestReview.reviewer'])
            ->needsCorrection()
            ->latest('submitted_at')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int, ProductionReport> */
    #[Computed]
    public function recentlyVerified(): Collection
    {
        return ProductionReport::query()
            ->with(['user', 'assignment.workOrder.product', 'latestReview.reviewer'])
            ->verified()
            ->whereHas('latestReview', fn ($q) => $q->whereDate('reviewed_at', today()))
            ->latest('updated_at')
            ->limit(8)
            ->get();
    }

    /** @return array<string, int> */
    #[Computed]
    public function teamTotals(): array
    {
        $production = app(OperationsMetrics::class)->productionForDate();

        return [
            'reported' => $production['reported'],
            'verified' => $production['verified'],
            'pending' => $production['pending'],
            'pending_reports' => $production['pending_reports'],
        ];
    }

    // ----------------------------------------------------------------- actions

    public function verify(int $reportId): void
    {
        $report = ProductionReport::findOrFail($reportId);

        try {
            app(ProductionReportService::class)->review(
                auth()->user(),
                $report,
                ReportStatus::VERIFIED,
            );
        } catch (ProductionReportException $e) {
            session()->flash('error', $e->getMessage());
            $this->refreshQueue();

            return;
        }

        session()->flash('status', sprintf(
            'Laporan %s terverifikasi. %s %s masuk ke hasil resmi.',
            $report->report_number,
            number_format($report->quantity_completed, 0, ',', '.'),
            $report->assignment?->unit() ?? 'pcs',
        ));

        $this->refreshQueue();
    }

    public function startCorrection(int $reportId): void
    {
        $this->correctionFor = $reportId;
        $this->correctionNotes = '';
        $this->resetErrorBag();
    }

    public function cancelCorrection(): void
    {
        $this->correctionFor = null;
        $this->correctionNotes = '';
        $this->resetErrorBag();
    }

    public function requestCorrection(): void
    {
        $this->validate(
            ['correctionNotes' => ['required', 'string', 'min:5', 'max:1000']],
            [
                'correctionNotes.required' => 'Tuliskan alasan perbaikan agar operator tahu apa yang harus diperbaiki.',
                'correctionNotes.min' => 'Jelaskan sedikit lebih rinci.',
            ],
        );

        $report = ProductionReport::findOrFail($this->correctionFor);

        try {
            app(ProductionReportService::class)->review(
                auth()->user(),
                $report,
                ReportStatus::NEEDS_CORRECTION,
                $this->correctionNotes,
            );
        } catch (ProductionReportException $e) {
            $this->addError('correctionNotes', $e->getMessage());

            return;
        }

        session()->flash('status', sprintf(
            'Laporan %s ditandai Perlu Perbaikan. Operator akan melihat catatan Anda.',
            $report->report_number,
        ));

        $this->cancelCorrection();
        $this->refreshQueue();
    }

    private function refreshQueue(): void
    {
        unset($this->pending, $this->needsCorrection, $this->recentlyVerified, $this->teamTotals);
    }

    public function render()
    {
        return view('livewire.supervisor.review-queue');
    }
}
