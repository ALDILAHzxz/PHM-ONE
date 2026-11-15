<?php

namespace App\Livewire\Management;

use App\Models\AuditEvent;
use App\Models\CustomerOrder;
use App\Models\ProductionReport;
use App\Services\OperationsMetrics;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Ringkasan Perusahaan.
 *
 * Polls on the same interval as the supervisor queue, so a report submitted in
 * another browser session appears here without anyone re-entering anything.
 * Reported and verified are always presented as two distinct figures.
 */
class CompanyOverview extends Component
{
    #[Computed]
    public function kpis(): array
    {
        return app(OperationsMetrics::class)->headlineKpis();
    }

    #[Computed]
    public function progress(): Collection
    {
        return app(OperationsMetrics::class)->productionProgress(6);
    }

    #[Computed]
    public function shipping(): array
    {
        return app(OperationsMetrics::class)->shippingSummary();
    }

    #[Computed]
    public function alerts(): Collection
    {
        return app(OperationsMetrics::class)->alerts()->take(5);
    }

    #[Computed]
    public function approachingDeadline(): Collection
    {
        return CustomerOrder::approachingDeadline(7)->with(['partner', 'product'])->limit(5)->get();
    }

    #[Computed]
    public function pendingReviews(): Collection
    {
        return ProductionReport::query()
            ->with(['user', 'assignment.workOrder.product'])
            ->pending()
            ->oldest('submitted_at')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentActivity(): Collection
    {
        return AuditEvent::query()->with('actor')->feed()->limit(8)->get();
    }

    public function render()
    {
        return view('livewire.management.company-overview');
    }
}
