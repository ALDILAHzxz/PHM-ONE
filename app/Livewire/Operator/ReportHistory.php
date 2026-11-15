<?php

namespace App\Livewire\Operator;

use App\Models\ProductionReport;
use App\Support\ReportStatus;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Riwayat Saya — the operator's own submissions, with their review outcome.
 * Scoped to auth()->id() in the query itself, never by a request parameter.
 */
class ReportHistory extends Component
{
    use WithPagination;

    #[Url(as: 'status', keep: false)]
    public string $statusFilter = 'semua';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        return [
            'semua' => 'Semua',
            ReportStatus::PENDING => ReportStatus::label(ReportStatus::PENDING),
            ReportStatus::VERIFIED => ReportStatus::label(ReportStatus::VERIFIED),
            ReportStatus::NEEDS_CORRECTION => ReportStatus::label(ReportStatus::NEEDS_CORRECTION),
        ];
    }

    public function render()
    {
        $reports = ProductionReport::query()
            ->with(['assignment.workOrder.product', 'latestReview.reviewer'])
            ->where('user_id', auth()->id())
            ->when(
                $this->statusFilter !== 'semua',
                fn ($q) => $q->where('status', $this->statusFilter)
            )
            ->latest('submitted_at')
            ->paginate(10);

        return view('livewire.operator.report-history', [
            'reports' => $reports,
            'statusOptions' => $this->statusOptions(),
        ]);
    }
}
