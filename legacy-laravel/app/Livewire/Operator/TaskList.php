<?php

namespace App\Livewire\Operator;

use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Support\ReportStatus;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Tugas Saya — deliberately the simplest major screen in the application.
 * No charts, no management metrics: today's work, progress, one big action.
 *
 * Polls so an operator sees their own report move from "Menunggu Pemeriksaan"
 * to "Terverifikasi" without reloading the page.
 */
class TaskList extends Component
{
    /** @return Collection<int, ProductionAssignment> */
    #[Computed]
    public function assignments(): Collection
    {
        return ProductionAssignment::query()
            ->with(['workOrder.product', 'stage', 'workStation', 'workShift'])
            ->withProgress()
            ->forUser(auth()->id())
            ->open()
            ->whereDate('planned_date', '<=', today())
            ->orderBy('planned_date')
            ->get();
    }

    /** @return Collection<int, ProductionAssignment> */
    #[Computed]
    public function upcoming(): Collection
    {
        return ProductionAssignment::query()
            ->with(['workOrder.product'])
            ->withProgress()
            ->forUser(auth()->id())
            ->open()
            ->whereDate('planned_date', '>', today())
            ->orderBy('planned_date')
            ->limit(3)
            ->get();
    }

    /** @return Collection<int, ProductionReport> */
    #[Computed]
    public function latestReports(): Collection
    {
        return ProductionReport::query()
            ->with(['assignment.workOrder.product', 'latestReview.reviewer'])
            ->where('user_id', auth()->id())
            ->latest('submitted_at')
            ->limit(4)
            ->get();
    }

    /** Reports the operator must redo — surfaced as an alert, not hidden. */
    #[Computed]
    public function needsCorrection(): Collection
    {
        return ProductionReport::query()
            ->with(['assignment.workOrder.product', 'latestReview'])
            ->where('user_id', auth()->id())
            ->needsCorrection()
            ->latest('submitted_at')
            ->get();
    }

    /** @return array{reported: int, verified: int, pending: int} */
    #[Computed]
    public function todayTotals(): array
    {
        $rows = ProductionReport::query()
            ->where('user_id', auth()->id())
            ->submittedToday()
            ->selectRaw('status, SUM(quantity_completed) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = (int) ($rows[ReportStatus::PENDING] ?? 0);
        $verified = (int) ($rows[ReportStatus::VERIFIED] ?? 0);

        return [
            'reported' => $pending + $verified,
            'verified' => $verified,
            'pending' => $pending,
        ];
    }

    public function render()
    {
        return view('livewire.operator.task-list');
    }
}
