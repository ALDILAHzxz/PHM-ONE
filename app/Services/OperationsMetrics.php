<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Models\Shipment;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\ReportStatus;
use App\Support\ShipmentStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The single source of truth for every operational number in the application.
 *
 * The management dashboard and the Beesha AI tools both read from here, so a
 * figure quoted by the assistant can never disagree with the dashboard.
 *
 * Reported vs verified is kept strictly separate everywhere (section 23).
 */
class OperationsMetrics
{
    /**
     * Company-wide production for a given day.
     *
     * @return array{date: string, reported: int, verified: int, pending: int, pending_reports: int, reports: int}
     */
    public function productionForDate(?Carbon $date = null): array
    {
        $date ??= today();

        $rows = ProductionReport::query()
            ->whereDate('submitted_at', $date->toDateString())
            ->selectRaw('status, SUM(quantity_completed) as total, COUNT(*) as jumlah')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $pending = (int) ($rows[ReportStatus::PENDING]->total ?? 0);
        $verified = (int) ($rows[ReportStatus::VERIFIED]->total ?? 0);

        return [
            'date' => $date->toDateString(),
            'reported' => $pending + $verified, // pending + verified, never corrected/voided
            'verified' => $verified,
            'pending' => $pending,
            'pending_reports' => (int) ($rows[ReportStatus::PENDING]->jumlah ?? 0),
            'reports' => (int) $rows->sum('jumlah'),
        ];
    }

    /**
     * The four headline KPI cards (section 23).
     *
     * @return array<string, mixed>
     */
    public function headlineKpis(): array
    {
        $production = $this->productionForDate();

        return [
            'active_orders' => CustomerOrder::active()->count(),
            'reported_today' => $production['reported'],
            'verified_today' => $production['verified'],
            'pending_today' => $production['pending'],
            'pending_reports' => $production['pending_reports'],
            'shipments_today' => (int) Shipment::query()
                ->whereIn('status', ShipmentStatus::COUNTS_AS_SHIPPED)
                ->shippedToday()
                ->sum('quantity_shipped'),
            'attention_count' => $this->alerts()->count(),
            'as_of' => now(),
        ];
    }

    /**
     * Operational problems worth a manager's attention, most urgent first.
     * Derived from live records — there is no separate alerts table to drift.
     *
     * @return Collection<int, array{severity: string, title: string, detail: string, route: ?string}>
     */
    public function alerts(): Collection
    {
        $alerts = collect();

        // 1. Orders already past their delivery target.
        $overdue = CustomerOrder::active()
            ->whereDate('delivery_target_date', '<', today())
            ->with('partner')
            ->get();

        foreach ($overdue as $order) {
            $alerts->push([
                'severity' => 'critical',
                'title' => sprintf('Pesanan %s melewati target pengiriman', $order->order_number),
                'detail' => sprintf(
                    '%s · target %s · sisa %s %s belum dikirim.',
                    $order->partner->name,
                    $order->delivery_target_date->translatedFormat('d M Y'),
                    number_format($order->remainingToShipQuantity(), 0, ',', '.'),
                    $order->product?->unit ?? 'pcs',
                ),
                'route' => 'orders.show',
                'route_param' => $order->id,
            ]);
        }

        // 2. Orders due within two days.
        $soon = CustomerOrder::active()
            ->whereDate('delivery_target_date', '>=', today())
            ->whereDate('delivery_target_date', '<=', today()->addDays(2))
            ->with('partner')
            ->get();

        foreach ($soon as $order) {
            $alerts->push([
                'severity' => 'warning',
                'title' => sprintf('Pesanan %s mendekati tenggat', $order->order_number),
                'detail' => sprintf(
                    '%s · target %s (%s).',
                    $order->partner->name,
                    $order->delivery_target_date->translatedFormat('d M Y'),
                    $order->delivery_target_date->isToday() ? 'hari ini' : $order->delivery_target_date->diffForHumans(),
                ),
                'route' => 'orders.show',
                'route_param' => $order->id,
            ]);
        }

        // 3. Production issues reported by operators today.
        $issues = ProductionReport::query()
            ->withIssue()
            ->whereIn('status', [ReportStatus::PENDING, ReportStatus::VERIFIED])
            ->whereDate('submitted_at', '>=', today()->subDay())
            ->with(['user', 'assignment.workOrder'])
            ->latest('submitted_at')
            ->get();

        foreach ($issues as $report) {
            $alerts->push([
                'severity' => 'warning',
                'title' => sprintf('Kendala %s dilaporkan', mb_strtolower($report->issueLabel())),
                'detail' => sprintf(
                    '%s · %s · %s',
                    $report->user?->full_name ?? 'Operator',
                    $report->assignment?->woNumber() ?? '—',
                    $report->issue_description ?? 'Tanpa penjelasan.',
                ),
                'route' => null,
                'route_param' => null,
            ]);
        }

        // 4. Review backlog.
        $pendingCount = ProductionReport::pending()->count();

        if ($pendingCount > 0) {
            $alerts->push([
                'severity' => $pendingCount >= 10 ? 'warning' : 'info',
                'title' => sprintf('%d laporan menunggu pemeriksaan', $pendingCount),
                'detail' => 'Jumlah terverifikasi belum bertambah sampai supervisor memeriksa laporan ini.',
                'route' => null,
                'route_param' => null,
            ]);
        }

        // 5. Shipments stuck with a reported problem.
        $stuck = Shipment::query()
            ->where('status', ShipmentStatus::ISSUE)
            ->with('order.partner')
            ->get();

        foreach ($stuck as $shipment) {
            $alerts->push([
                'severity' => 'critical',
                'title' => sprintf('Pengiriman %s bermasalah', $shipment->shipment_number),
                'detail' => sprintf(
                    '%s · %s',
                    $shipment->order?->partner?->name ?? 'Mitra',
                    $shipment->notes ?: 'Driver melaporkan kendala.',
                ),
                'route' => null,
                'route_param' => null,
            ]);
        }

        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];

        return $alerts->sortBy(fn (array $a) => $order[$a['severity']] ?? 3)->values();
    }

    /**
     * Per-work-order production progress for the management production view.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function productionProgress(int $limit = 10): Collection
    {
        return ProductionAssignment::query()
            ->with(['workOrder.product', 'user'])
            ->withProgress()
            ->whereDate('planned_date', '<=', today())
            ->whereIn('status', [
                ProductionAssignment::STATUS_ASSIGNED,
                ProductionAssignment::STATUS_RUNNING,
                ProductionAssignment::STATUS_DONE,
            ])
            ->orderByDesc('planned_date')
            ->limit($limit)
            ->get()
            ->map(fn (ProductionAssignment $a) => [
                'assignment' => $a,
                'operator' => $a->user?->full_name ?? '—',
                'product' => $a->productName(),
                'wo' => $a->woNumber(),
                'target' => $a->targetQty(),
                'reported' => $a->reportedQty(),
                'verified' => $a->verifiedQty(),
                'remaining' => $a->remainingQty(),
                'unit' => $a->unit(),
            ]);
    }

    /**
     * Shipping performance snapshot.
     *
     * @return array<string, mixed>
     */
    public function shippingSummary(): array
    {
        $byStatus = Shipment::query()
            ->selectRaw('status, COUNT(*) as jumlah, SUM(quantity_shipped) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'today_quantity' => (int) Shipment::query()
                ->whereIn('status', ShipmentStatus::COUNTS_AS_SHIPPED)
                ->shippedToday()
                ->sum('quantity_shipped'),
            'today_count' => Shipment::query()->shippedToday()->count(),
            'in_transit' => (int) ($byStatus[ShipmentStatus::IN_TRANSIT]->jumlah ?? 0),
            'arrived' => (int) ($byStatus[ShipmentStatus::ARRIVED]->jumlah ?? 0),
            'received' => (int) ($byStatus[ShipmentStatus::RECEIVED]->jumlah ?? 0),
            'prepared' => (int) ($byStatus[ShipmentStatus::PREPARED]->jumlah ?? 0),
            'issues' => (int) ($byStatus[ShipmentStatus::ISSUE]->jumlah ?? 0),
            'total_delivered_quantity' => (int) ($byStatus[ShipmentStatus::RECEIVED]->total ?? 0),
            'as_of' => now(),
        ];
    }

    /**
     * Order fulfilment overview.
     *
     * @return array<string, mixed>
     */
    public function orderSummary(): array
    {
        $byStatus = CustomerOrder::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return [
            'total' => (int) $byStatus->sum(),
            'active' => CustomerOrder::active()->count(),
            'new_today' => CustomerOrder::whereDate('created_at', today())->count(),
            'by_status' => collect(OrderStatus::OPTIONS)
                ->map(fn (string $label, string $code) => [
                    'label' => $label,
                    'count' => (int) ($byStatus[$code] ?? 0),
                ])
                ->values()
                ->all(),
            'approaching' => CustomerOrder::approachingDeadline(7)->with('partner')->get(),
            'as_of' => now(),
        ];
    }

    /**
     * One operator's own numbers, used by both the operator screens and the
     * assistant's `getMyProductionSummary` tool.
     *
     * @return array<string, mixed>
     */
    public function summaryForUser(User $user, ?Carbon $date = null): array
    {
        $date ??= today();

        $rows = ProductionReport::query()
            ->where('user_id', $user->id)
            ->whereDate('submitted_at', $date->toDateString())
            ->selectRaw('status, SUM(quantity_completed) as total, COUNT(*) as jumlah')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $pending = (int) ($rows[ReportStatus::PENDING]->total ?? 0);
        $verified = (int) ($rows[ReportStatus::VERIFIED]->total ?? 0);

        $assignments = ProductionAssignment::query()
            ->with('workOrder.product')
            ->withProgress()
            ->forUser($user->id)
            ->open()
            ->whereDate('planned_date', '<=', $date->toDateString())
            ->get();

        return [
            'date' => $date->toDateString(),
            'reported_today' => $pending + $verified,
            'verified_today' => $verified,
            'pending_today' => $pending,
            'report_count_today' => (int) $rows->sum('jumlah'),
            'target_total' => (int) $assignments->sum(fn (ProductionAssignment $a) => $a->targetQty()),
            'reported_total' => (int) $assignments->sum(fn (ProductionAssignment $a) => $a->reportedQty()),
            'remaining_total' => (int) $assignments->sum(fn (ProductionAssignment $a) => $a->remainingQty()),
            'assignments' => $assignments,
        ];
    }
}
