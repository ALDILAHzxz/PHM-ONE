<?php

namespace App\Services\Beesha;

use App\Models\CustomerOrder;
use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Models\Shipment;
use App\Models\User;
use App\Services\OperationsMetrics;
use App\Support\Permissions;
use App\Support\ShipmentStatus;

/**
 * The ONLY way Beesha reaches company data.
 *
 * Rules enforced here, not in the prompt:
 *   - every tool declares the permission it requires
 *   - every call re-checks that permission against the live user
 *   - results are always scoped (an operator's tools filter by their own id)
 *   - there is no free-form SQL, and no tool performs a write
 *
 * A model can therefore only ever see data the signed-in user could have
 * opened themselves in the interface.
 */
class BeeshaToolRegistry
{
    public function __construct(private readonly OperationsMetrics $metrics) {}

    /**
     * @return array<string, array{permission: string, description: string}>
     */
    public function catalogue(): array
    {
        return [
            'getMyAssignments' => [
                'permission' => Permissions::PRODUCTION_REPORT_CREATE,
                'description' => 'Daftar penugasan produksi aktif milik pengguna sendiri, termasuk target dan sisa.',
            ],
            'getMyProductionSummary' => [
                'permission' => Permissions::PRODUCTION_REPORT_VIEW_OWN,
                'description' => 'Ringkasan hasil produksi pengguna sendiri hari ini.',
            ],
            'getMyReportStatus' => [
                'permission' => Permissions::PRODUCTION_REPORT_VIEW_OWN,
                'description' => 'Status laporan terakhir milik pengguna sendiri.',
            ],
            'getPendingReviews' => [
                'permission' => Permissions::PRODUCTION_REPORT_REVIEW,
                'description' => 'Laporan produksi yang masih menunggu pemeriksaan supervisor.',
            ],
            'getTeamProductionSummary' => [
                'permission' => Permissions::PRODUCTION_REPORT_VIEW_TEAM,
                'description' => 'Hasil produksi seluruh tim hari ini beserta penugasan yang masih aktif.',
            ],
            'getOrderSummary' => [
                'permission' => Permissions::ORDER_VIEW,
                'description' => 'Ringkasan pesanan mitra dan pesanan yang mendekati tenggat.',
            ],
            'getShippingSummary' => [
                'permission' => Permissions::SHIPMENT_VIEW,
                'description' => 'Ringkasan pengiriman: dikirim hari ini, dalam perjalanan, diterima.',
            ],
            'getMyShipments' => [
                'permission' => Permissions::SHIPMENT_VIEW_OWN,
                'description' => 'Daftar pengiriman yang ditugaskan kepada driver yang sedang masuk.',
            ],
            'getAuthorizedOperationalKPIs' => [
                'permission' => Permissions::KPI_VIEW,
                'description' => 'KPI operasional perusahaan: pesanan aktif, produksi dilaporkan dan terverifikasi.',
            ],
            'getOperationalAlerts' => [
                'permission' => Permissions::DASHBOARD_VIEW,
                'description' => 'Peringatan operasional terbaru: tenggat, kendala produksi, pengiriman bermasalah.',
            ],
        ];
    }

    /** Tool names this specific user is allowed to invoke. */
    public function availableTo(User $user): array
    {
        return array_keys(array_filter(
            $this->catalogue(),
            fn (array $tool) => $user->hasPermission($tool['permission'])
        ));
    }

    public function userCanCall(User $user, string $tool): bool
    {
        $definition = $this->catalogue()[$tool] ?? null;

        return $definition !== null && $user->hasPermission($definition['permission']);
    }

    /**
     * Execute a tool for a user.
     *
     * @return array<string, mixed>
     *
     * @throws BeeshaAuthorizationException
     */
    public function call(User $user, string $tool, array $arguments = []): array
    {
        if (! $this->userCanCall($user, $tool)) {
            throw new BeeshaAuthorizationException($tool);
        }

        return match ($tool) {
            'getMyAssignments' => $this->getMyAssignments($user),
            'getMyProductionSummary' => $this->getMyProductionSummary($user),
            'getMyReportStatus' => $this->getMyReportStatus($user),
            'getPendingReviews' => $this->getPendingReviews(),
            'getTeamProductionSummary' => $this->getTeamProductionSummary(),
            'getOrderSummary' => $this->getOrderSummary(),
            'getShippingSummary' => $this->getShippingSummary(),
            'getMyShipments' => $this->getMyShipments($user),
            'getAuthorizedOperationalKPIs' => $this->getAuthorizedOperationalKPIs(),
            'getOperationalAlerts' => $this->getOperationalAlerts(),
            default => throw new BeeshaAuthorizationException($tool),
        };
    }

    // ---------------------------------------------------------------- operator

    private function getMyAssignments(User $user): array
    {
        $assignments = ProductionAssignment::query()
            ->with('workOrder.product')
            ->withProgress()
            ->forUser($user->id)           // scoped: own work only
            ->open()
            ->whereDate('planned_date', '<=', today())
            ->get();

        return [
            'period' => 'hari ini, '.today()->translatedFormat('d F Y'),
            'count' => $assignments->count(),
            'items' => $assignments->map(fn (ProductionAssignment $a) => [
                'produk' => $a->productName(),
                'work_order' => $a->woNumber(),
                'tahap' => $a->stage?->name,
                'target' => $a->targetQty(),
                'dilaporkan' => $a->reportedQty(),
                'terverifikasi' => $a->verifiedQty(),
                'sisa' => $a->remainingQty(),
                'satuan' => $a->unit(),
            ])->all(),
        ];
    }

    private function getMyProductionSummary(User $user): array
    {
        $summary = $this->metrics->summaryForUser($user);

        return [
            'period' => 'hari ini, '.today()->translatedFormat('d F Y'),
            'dilaporkan_hari_ini' => $summary['reported_today'],
            'terverifikasi_hari_ini' => $summary['verified_today'],
            'menunggu_pemeriksaan' => $summary['pending_today'],
            'jumlah_laporan' => $summary['report_count_today'],
            'target_aktif' => $summary['target_total'],
            'sisa_aktif' => $summary['remaining_total'],
            'satuan' => 'pcs',
        ];
    }

    private function getMyReportStatus(User $user): array
    {
        $reports = ProductionReport::query()
            ->with('assignment.workOrder.product', 'latestReview')
            ->where('user_id', $user->id)   // scoped: own reports only
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return [
            'count' => $reports->count(),
            'items' => $reports->map(fn (ProductionReport $r) => [
                'nomor' => $r->report_number,
                'jumlah' => $r->quantity_completed,
                'satuan' => $r->assignment?->unit() ?? 'pcs',
                'produk' => $r->assignment?->productName(),
                'status' => $r->statusLabel(),
                'waktu' => $r->submitted_at->translatedFormat('d M Y, H:i'),
                'catatan_pemeriksaan' => $r->latestReview?->review_notes,
            ])->all(),
        ];
    }

    // -------------------------------------------------------------- supervisor

    private function getPendingReviews(): array
    {
        $reports = ProductionReport::query()
            ->with(['user', 'assignment.workOrder.product'])
            ->pending()
            ->oldest('submitted_at')
            ->limit(15)
            ->get();

        $corrections = ProductionReport::needsCorrection()->count();

        return [
            'period' => 'saat ini',
            'menunggu_pemeriksaan' => $reports->count(),
            'total_unit_menunggu' => (int) $reports->sum('quantity_completed'),
            'perlu_perbaikan' => $corrections,
            'items' => $reports->map(fn (ProductionReport $r) => [
                'nomor' => $r->report_number,
                'operator' => $r->user?->full_name,
                'produk' => $r->assignment?->productName(),
                'work_order' => $r->assignment?->woNumber(),
                'jumlah' => $r->quantity_completed,
                'satuan' => $r->assignment?->unit() ?? 'pcs',
                'kendala' => $r->has_issue ? $r->issueLabel() : 'Tidak ada',
                'waktu' => $r->submitted_at->translatedFormat('d M Y, H:i'),
            ])->all(),
        ];
    }

    private function getTeamProductionSummary(): array
    {
        $production = $this->metrics->productionForDate();

        $active = ProductionAssignment::query()
            ->with(['user', 'workOrder.product'])
            ->withProgress()
            ->open()
            ->whereDate('planned_date', '<=', today())
            ->get();

        return [
            'period' => 'hari ini, '.today()->translatedFormat('d F Y'),
            'dilaporkan' => $production['reported'],
            'terverifikasi' => $production['verified'],
            'menunggu_pemeriksaan' => $production['pending'],
            'jumlah_laporan' => $production['reports'],
            'satuan' => 'pcs',
            'penugasan_aktif' => $active->map(fn (ProductionAssignment $a) => [
                'operator' => $a->user?->full_name,
                'produk' => $a->productName(),
                'work_order' => $a->woNumber(),
                'target' => $a->targetQty(),
                'dilaporkan' => $a->reportedQty(),
                'sisa' => $a->remainingQty(),
                'satuan' => $a->unit(),
            ])->all(),
            'catatan' => 'Terverifikasi hanya mencakup laporan yang sudah diperiksa supervisor.',
        ];
    }

    // ------------------------------------------------------------------ orders

    private function getOrderSummary(): array
    {
        $summary = $this->metrics->orderSummary();

        return [
            'period' => 'saat ini, '.now()->translatedFormat('d F Y H:i'),
            'total_pesanan' => $summary['total'],
            'pesanan_aktif' => $summary['active'],
            'pesanan_baru_hari_ini' => $summary['new_today'],
            'per_status' => $summary['by_status'],
            'mendekati_tenggat' => $summary['approaching']->map(fn (CustomerOrder $o) => [
                'nomor' => $o->order_number,
                'mitra' => $o->partner->name,
                'jumlah' => $o->quantity,
                'sudah_dikirim' => $o->shippedQuantity(),
                'sisa_kirim' => $o->remainingToShipQuantity(),
                'target_kirim' => $o->delivery_target_date->translatedFormat('d M Y'),
                'status' => $o->statusLabel(),
                'terlambat' => $o->isOverdue(),
            ])->all(),
        ];
    }

    // ---------------------------------------------------------------- shipping

    private function getShippingSummary(): array
    {
        $summary = $this->metrics->shippingSummary();

        return [
            'period' => 'hari ini, '.today()->translatedFormat('d F Y'),
            'dikirim_hari_ini_unit' => $summary['today_quantity'],
            'jumlah_pengiriman_hari_ini' => $summary['today_count'],
            'dalam_perjalanan' => $summary['in_transit'],
            'sudah_tiba' => $summary['arrived'],
            'sudah_diterima' => $summary['received'],
            'siap_dikirim' => $summary['prepared'],
            'bermasalah' => $summary['issues'],
            'total_unit_diterima' => $summary['total_delivered_quantity'],
        ];
    }

    private function getMyShipments(User $user): array
    {
        $shipments = Shipment::query()
            ->with('order.partner')
            ->forDriver($user->id)          // scoped: own deliveries only
            ->inProgress()
            ->orderBy('shipped_at')
            ->get();

        return [
            'period' => 'saat ini',
            'count' => $shipments->count(),
            'items' => $shipments->map(fn (Shipment $s) => [
                'nomor' => $s->shipment_number,
                'surat_jalan' => $s->delivery_note_number,
                'mitra' => $s->order?->partner?->name,
                'tujuan' => $s->destination,
                'jumlah' => $s->quantity_shipped,
                'status' => $s->statusLabel(),
                'tindakan_berikutnya' => array_map(
                    fn (string $next) => ShipmentStatus::actionLabel($next),
                    $s->allowedNextStatuses(),
                ),
            ])->all(),
        ];
    }

    // -------------------------------------------------------------- management

    private function getAuthorizedOperationalKPIs(): array
    {
        $kpi = $this->metrics->headlineKpis();

        return [
            'period' => 'hari ini, '.today()->translatedFormat('d F Y'),
            'diperbarui' => $kpi['as_of']->translatedFormat('d M Y H:i'),
            'pesanan_aktif' => $kpi['active_orders'],
            'produksi_dilaporkan' => $kpi['reported_today'],
            'produksi_terverifikasi' => $kpi['verified_today'],
            'menunggu_pemeriksaan_unit' => $kpi['pending_today'],
            'menunggu_pemeriksaan_laporan' => $kpi['pending_reports'],
            'pengiriman_hari_ini_unit' => $kpi['shipments_today'],
            'perlu_perhatian' => $kpi['attention_count'],
            'catatan' => 'Dilaporkan mencakup laporan yang belum diperiksa. Terverifikasi hanya laporan yang sudah disetujui supervisor.',
        ];
    }

    private function getOperationalAlerts(): array
    {
        $alerts = $this->metrics->alerts();

        return [
            'period' => 'saat ini, '.now()->translatedFormat('d F Y H:i'),
            'count' => $alerts->count(),
            'items' => $alerts->take(10)->map(fn (array $a) => [
                'tingkat' => $a['severity'],
                'judul' => $a['title'],
                'detail' => $a['detail'],
            ])->values()->all(),
        ];
    }
}
