<?php

namespace Database\Seeders;

use App\Models\AuditEvent;
use App\Models\CustomerOrder;
use App\Models\DeliveryStatusEvent;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Models\ProductionReview;
use App\Models\ProductionStage;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkShift;
use App\Models\WorkStation;
use App\Support\IssueCategory;
use App\Support\OrderStatus;
use App\Support\ReportStatus;
use App\Support\ShipmentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Synthetic operational data.
 *
 * The first work order (DEMO-WO-004) reproduces the exact demonstration case
 * from the master specification section 37:
 *
 *   Operator Budi Santoso — Knalpot Standar Motor A
 *   target 500 pcs, reported 320 pcs, verified 320 pcs, remaining 180 pcs
 *
 * so the cross-session real-time test can be run straight after seeding.
 */
class DemoOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $partners = Partner::pluck('id', 'code');
        $products = Product::pluck('id', 'sku');
        $stages = ProductionStage::pluck('id', 'code');
        $stations = WorkStation::pluck('id', 'code');
        $shifts = WorkShift::pluck('id', 'code');

        $operator = User::where('employee_number', 'PHM-EMP-001')->firstOrFail();
        $supervisor = User::where('employee_number', 'PHM-EMP-002')->firstOrFail();
        $adminOrder = User::where('employee_number', 'PHM-EMP-003')->firstOrFail();
        $ppic = User::where('employee_number', 'PHM-EMP-004')->firstOrFail();
        $warehouse = User::where('employee_number', 'PHM-EMP-005')->firstOrFail();
        $driver = User::where('employee_number', 'PHM-EMP-006')->firstOrFail();
        $driver2 = User::where('employee_number', 'PHM-EMP-013')->firstOrFail();
        $operator2 = User::where('employee_number', 'PHM-EMP-010')->firstOrFail();
        $operator3 = User::where('employee_number', 'PHM-EMP-011')->firstOrFail();
        $operator4 = User::where('employee_number', 'PHM-EMP-012')->firstOrFail();

        // ------------------------------------------------------------- orders
        $orders = [
            [
                'order_number' => 'DEMO-PO-001',
                'partner' => 'MTR-01',
                'po_number' => 'PO-MMS-2601',
                'product' => 'KNL-STD-A',
                'quantity' => 1500,
                'target' => today()->addDays(9),
                'status' => OrderStatus::IN_PRODUCTION,
            ],
            [
                'order_number' => 'DEMO-PO-002',
                'partner' => 'MTR-02',
                'po_number' => 'PO-APN-4412',
                'product' => 'KNL-STD-B',
                'quantity' => 800,
                'target' => today()->addDays(4),
                'status' => OrderStatus::IN_PRODUCTION,
            ],
            [
                'order_number' => 'DEMO-PO-003',
                'partner' => 'MTR-03',
                'po_number' => 'PO-RJ-0098',
                'product' => 'KNL-RCG-C',
                'quantity' => 300,
                'target' => today()->addDays(2),
                'status' => OrderStatus::READY,
            ],
            [
                'order_number' => 'DEMO-PO-004',
                'partner' => 'MTR-04',
                'po_number' => 'PO-GOM-7731',
                'product' => 'LHR-KNL-01',
                'quantity' => 2000,
                'target' => today()->addDays(16),
                'status' => OrderStatus::NEW,
            ],
            [
                'order_number' => 'DEMO-PO-005',
                'partner' => 'MTR-01',
                'po_number' => 'PO-MMS-2588',
                'product' => 'BRK-KNL-02',
                'quantity' => 1200,
                'target' => today()->addDays(1),
                'status' => OrderStatus::PARTIALLY_SHIPPED,
            ],
            [
                'order_number' => 'DEMO-PO-006',
                'partner' => 'MTR-02',
                'po_number' => 'PO-APN-4350',
                'product' => 'KNL-STD-A',
                'quantity' => 600,
                'target' => today()->subDays(1),
                'status' => OrderStatus::PARTIALLY_SHIPPED,
            ],
        ];

        $orderModels = [];

        foreach ($orders as $row) {
            $orderModels[$row['order_number']] = CustomerOrder::updateOrCreate(
                ['order_number' => $row['order_number']],
                [
                    'partner_id' => $partners[$row['partner']],
                    'po_number' => $row['po_number'],
                    'product_id' => $products[$row['product']],
                    'quantity' => $row['quantity'],
                    'delivery_target_date' => $row['target'],
                    'status' => $row['status'],
                    'notes' => null,
                    'created_by' => $adminOrder->id,
                ]
            );
        }

        // --------------------------------------------------------- work orders
        $workOrders = [
            [
                'wo_number' => 'DEMO-WO-004',
                'order' => 'DEMO-PO-001',
                'product' => 'KNL-STD-A',
                'target' => 500,
                'date' => today(),
                'status' => 'berjalan',
            ],
            [
                'wo_number' => 'DEMO-WO-005',
                'order' => 'DEMO-PO-002',
                'product' => 'KNL-STD-B',
                'target' => 400,
                'date' => today(),
                'status' => 'berjalan',
            ],
            [
                'wo_number' => 'DEMO-WO-006',
                'order' => 'DEMO-PO-003',
                'product' => 'KNL-RCG-C',
                'target' => 300,
                'date' => today()->subDays(2),
                'status' => 'selesai',
            ],
            [
                'wo_number' => 'DEMO-WO-007',
                'order' => 'DEMO-PO-005',
                'product' => 'BRK-KNL-02',
                'target' => 600,
                'date' => today(),
                'status' => 'berjalan',
            ],
            [
                'wo_number' => 'DEMO-WO-008',
                'order' => 'DEMO-PO-004',
                'product' => 'LHR-KNL-01',
                'target' => 700,
                'date' => today()->addDays(2),
                'status' => 'direncanakan',
            ],
        ];

        $woModels = [];

        foreach ($workOrders as $row) {
            $woModels[$row['wo_number']] = WorkOrder::updateOrCreate(
                ['wo_number' => $row['wo_number']],
                [
                    'customer_order_id' => $orderModels[$row['order']]->id,
                    'product_id' => $products[$row['product']],
                    'target_quantity' => $row['target'],
                    'planned_date' => $row['date'],
                    'status' => $row['status'],
                    'created_by' => $ppic->id,
                ]
            );
        }

        // --------------------------------------------------------- assignments
        // Specification section 37 baseline: 500 target / 320 reported+verified.
        $mainAssignment = ProductionAssignment::updateOrCreate(
            [
                'work_order_id' => $woModels['DEMO-WO-004']->id,
                'user_id' => $operator->id,
                'planned_date' => today(),
            ],
            [
                'work_shift_id' => $shifts['S1'],
                'production_stage_id' => $stages['LAS'],
                'work_station_id' => $stations['ST-02'],
                'assigned_quantity' => 500,
                'status' => ProductionAssignment::STATUS_RUNNING,
                'created_by' => $ppic->id,
            ]
        );

        $this->seedReports($mainAssignment, $operator, $supervisor, [
            ['qty' => 120, 'hours' => 30, 'status' => ReportStatus::VERIFIED],
            ['qty' => 100, 'hours' => 26, 'status' => ReportStatus::VERIFIED],
            ['qty' => 100, 'hours' => 6, 'status' => ReportStatus::VERIFIED],
        ]);

        // Second assignment for the same operator (yesterday, already closed)
        // so "Riwayat Saya" is not empty on first login.
        $pastAssignment = ProductionAssignment::updateOrCreate(
            [
                'work_order_id' => $woModels['DEMO-WO-006']->id,
                'user_id' => $operator->id,
                'planned_date' => today()->subDays(2),
            ],
            [
                'work_shift_id' => $shifts['S1'],
                'production_stage_id' => $stages['HALUS'],
                'work_station_id' => $stations['ST-04'],
                'assigned_quantity' => 150,
                'status' => ProductionAssignment::STATUS_DONE,
                'created_by' => $ppic->id,
            ]
        );

        $this->seedReports($pastAssignment, $operator, $supervisor, [
            ['qty' => 80, 'hours' => 50, 'status' => ReportStatus::VERIFIED],
            ['qty' => 70, 'hours' => 46, 'status' => ReportStatus::VERIFIED],
        ]);

        // Other operators — gives the supervisor queue and team views content.
        $teamAssignments = [
            [
                'wo' => 'DEMO-WO-005',
                'user' => $operator2,
                'qty' => 400,
                'stage' => 'POTONG',
                'station' => 'ST-01',
                'shift' => 'S1',
                'reports' => [
                    ['qty' => 150, 'hours' => 28, 'status' => ReportStatus::VERIFIED],
                    ['qty' => 60, 'hours' => 3, 'status' => ReportStatus::PENDING],
                ],
            ],
            [
                'wo' => 'DEMO-WO-007',
                'user' => $operator3,
                'qty' => 600,
                'stage' => 'CAT',
                'station' => 'ST-05',
                'shift' => 'S1',
                'reports' => [
                    ['qty' => 200, 'hours' => 27, 'status' => ReportStatus::VERIFIED],
                    ['qty' => 45, 'hours' => 2, 'status' => ReportStatus::PENDING, 'issue' => IssueCategory::MACHINE, 'issue_text' => 'Kompresor cat sempat mati sekitar 20 menit.'],
                ],
            ],
            [
                'wo' => 'DEMO-WO-007',
                'user' => $operator4,
                'qty' => 300,
                'stage' => 'RAKIT',
                'station' => 'ST-06',
                'shift' => 'S2',
                'reports' => [
                    ['qty' => 90, 'hours' => 20, 'status' => ReportStatus::NEEDS_CORRECTION, 'review_notes' => 'Jumlah tidak sesuai hasil hitung ulang di stasiun perakitan. Mohon dicatat ulang.'],
                    ['qty' => 55, 'hours' => 4, 'status' => ReportStatus::PENDING],
                ],
            ],
        ];

        foreach ($teamAssignments as $row) {
            $assignment = ProductionAssignment::updateOrCreate(
                [
                    'work_order_id' => $woModels[$row['wo']]->id,
                    'user_id' => $row['user']->id,
                    'planned_date' => today(),
                ],
                [
                    'work_shift_id' => $shifts[$row['shift']],
                    'production_stage_id' => $stages[$row['stage']],
                    'work_station_id' => $stations[$row['station']],
                    'assigned_quantity' => $row['qty'],
                    'status' => ProductionAssignment::STATUS_RUNNING,
                    'created_by' => $ppic->id,
                ]
            );

            $this->seedReports($assignment, $row['user'], $supervisor, $row['reports']);
        }

        // An unstarted assignment for tomorrow (empty-state coverage).
        ProductionAssignment::updateOrCreate(
            [
                'work_order_id' => $woModels['DEMO-WO-008']->id,
                'user_id' => $operator2->id,
                'planned_date' => today()->addDays(2),
            ],
            [
                'work_shift_id' => $shifts['S1'],
                'production_stage_id' => $stages['POTONG'],
                'work_station_id' => $stations['ST-01'],
                'assigned_quantity' => 350,
                'status' => ProductionAssignment::STATUS_ASSIGNED,
                'created_by' => $ppic->id,
            ]
        );

        // ----------------------------------------------------------- shipments
        $shipments = [
            [
                'number' => 'DEMO-SJ-001',
                'order' => 'DEMO-PO-005',
                'qty' => 400,
                'note' => 'SRT-2026-0001',
                'driver' => $driver,
                'status' => ShipmentStatus::RECEIVED,
                'shipped_at' => now()->subDays(2)->setTime(8, 30),
                'delivered_at' => now()->subDays(2)->setTime(13, 5),
                'recipient' => 'Bapak Hendra',
            ],
            [
                'number' => 'DEMO-SJ-002',
                'order' => 'DEMO-PO-006',
                'qty' => 250,
                'note' => 'SRT-2026-0002',
                'driver' => $driver2,
                'status' => ShipmentStatus::RECEIVED,
                'shipped_at' => now()->subDay()->setTime(9, 0),
                'delivered_at' => now()->subDay()->setTime(15, 20),
                'recipient' => 'Ibu Ratna',
            ],
            [
                'number' => 'DEMO-SJ-003',
                'order' => 'DEMO-PO-003',
                'qty' => 150,
                'note' => 'SRT-2026-0003',
                'driver' => $driver,
                'status' => ShipmentStatus::IN_TRANSIT,
                'shipped_at' => now()->setTime(7, 45),
                'delivered_at' => null,
                'recipient' => null,
            ],
            [
                'number' => 'DEMO-SJ-004',
                'order' => 'DEMO-PO-005',
                'qty' => 200,
                'note' => null,
                'driver' => $driver,
                'status' => ShipmentStatus::PREPARED,
                'shipped_at' => null,
                'delivered_at' => null,
                'recipient' => null,
            ],
        ];

        foreach ($shipments as $row) {
            $shipment = Shipment::updateOrCreate(
                ['shipment_number' => $row['number']],
                [
                    'customer_order_id' => $orderModels[$row['order']]->id,
                    'quantity_shipped' => $row['qty'],
                    'delivery_note_number' => $row['note'],
                    'driver_id' => $row['driver']->id,
                    'destination' => $orderModels[$row['order']]->partner->address,
                    'status' => $row['status'],
                    'shipped_at' => $row['shipped_at'],
                    'delivered_at' => $row['delivered_at'],
                    'recorded_by' => $warehouse->id,
                    'idempotency_key' => 'seed-'.Str::slug($row['number']),
                ]
            );

            if ($shipment->events()->exists()) {
                continue;
            }

            $timeline = [[ShipmentStatus::PREPARED, $row['shipped_at'] ?? now(), $warehouse, null]];

            if (in_array($row['status'], [ShipmentStatus::IN_TRANSIT, ShipmentStatus::ARRIVED, ShipmentStatus::RECEIVED], true)) {
                $timeline[] = [ShipmentStatus::IN_TRANSIT, $row['shipped_at'], $row['driver'], null];
            }

            if ($row['status'] === ShipmentStatus::RECEIVED) {
                $timeline[] = [ShipmentStatus::ARRIVED, $row['delivered_at']?->copy()->subMinutes(20), $row['driver'], null];
                $timeline[] = [ShipmentStatus::RECEIVED, $row['delivered_at'], $row['driver'], $row['recipient']];
            }

            foreach ($timeline as [$status, $at, $actor, $recipient]) {
                DeliveryStatusEvent::create([
                    'shipment_id' => $shipment->id,
                    'status' => $status,
                    'actor_id' => $actor->id,
                    'recipient_name' => $recipient,
                    'occurred_at' => $at ?? now(),
                ]);
            }
        }

        // --------------------------------------------------------- audit trail
        if (AuditEvent::count() === 0) {
            $events = [
                [$adminOrder, 'customer_order.created', 'CustomerOrder', $orderModels['DEMO-PO-004']->id, 'Dewi Kartika menambahkan pesanan DEMO-PO-004 dari PT Garuda Otoparts Mandiri.', 180],
                [$ppic, 'work_order.created', 'WorkOrder', $woModels['DEMO-WO-004']->id, 'Agus Wijaya membuat work order DEMO-WO-004 (500 pcs).', 150],
                [$ppic, 'assignment.created', 'ProductionAssignment', $mainAssignment->id, 'Agus Wijaya menugaskan Budi Santoso pada DEMO-WO-004.', 145],
                [$warehouse, 'shipment.created', 'Shipment', null, 'Rudi Hartono mencatat pengiriman DEMO-SJ-003 (150 pcs).', 60],
                [$driver, 'shipment.status_changed', 'Shipment', null, 'Joko Prasetyo memulai pengiriman DEMO-SJ-003.', 55],
            ];

            foreach ($events as [$actor, $action, $type, $id, $summary, $minutesAgo]) {
                AuditEvent::create([
                    'actor_id' => $actor->id,
                    'action' => $action,
                    'entity_type' => $type,
                    'entity_id' => $id,
                    'summary' => $summary,
                    'metadata' => ['seeded' => true],
                    'created_at' => now()->subMinutes($minutesAgo),
                    'updated_at' => now()->subMinutes($minutesAgo),
                ]);
            }
        }
    }

    /**
     * Create reports for an assignment, each with the review record that put it
     * into its final status, so verified totals are never fabricated.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function seedReports(
        ProductionAssignment $assignment,
        User $author,
        User $reviewer,
        array $rows
    ): void {
        if ($assignment->reports()->exists()) {
            return;
        }

        foreach ($rows as $index => $row) {
            $submittedAt = now()->subHours((int) $row['hours']);
            $status = $row['status'];

            $report = ProductionReport::create([
                'report_number' => sprintf(
                    'LP-%s-%04d',
                    $submittedAt->format('Ymd'),
                    ProductionReport::whereDate('submitted_at', $submittedAt->toDateString())->count() + 1
                ),
                'production_assignment_id' => $assignment->id,
                'user_id' => $author->id,
                'quantity_completed' => (int) $row['qty'],
                'has_issue' => isset($row['issue']),
                'issue_category' => $row['issue'] ?? null,
                'issue_description' => $row['issue_text'] ?? null,
                'additional_notes' => $row['notes'] ?? null,
                'status' => $status,
                'submitted_at' => $submittedAt,
                'idempotency_key' => 'seed-'.$assignment->id.'-'.$index.'-'.Str::random(8),
                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ]);

            if (in_array($status, [ReportStatus::VERIFIED, ReportStatus::NEEDS_CORRECTION], true)) {
                ProductionReview::create([
                    'production_report_id' => $report->id,
                    'reviewed_by' => $reviewer->id,
                    'status' => $status,
                    'review_notes' => $row['review_notes'] ?? null,
                    'reviewed_at' => $submittedAt->copy()->addHours(1),
                    'created_at' => $submittedAt->copy()->addHours(1),
                    'updated_at' => $submittedAt->copy()->addHours(1),
                ]);
            }
        }
    }
}
