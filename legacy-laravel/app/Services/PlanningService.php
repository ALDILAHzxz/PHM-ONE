<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\ProductionAssignment;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Permissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanningService
{
    public function __construct(
        private readonly DocumentNumbering $numbering,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{customer_order_id: ?int, product_id: int, target_quantity: int, planned_date: string, notes: ?string}  $data
     */
    public function createWorkOrder(User $user, array $data): WorkOrder
    {
        abort_unless($user->hasPermission(Permissions::WORK_ORDER_MANAGE), 403);

        return DB::transaction(function () use ($user, $data) {
            // Do not plan more than the customer actually ordered.
            if ($data['customer_order_id'] !== null) {
                $order = CustomerOrder::with('workOrders')->findOrFail($data['customer_order_id']);
                $alreadyPlanned = (int) $order->workOrders()->sum('target_quantity');

                if ($alreadyPlanned + $data['target_quantity'] > $order->quantity) {
                    throw ValidationException::withMessages([
                        'targetQuantity' => sprintf(
                            'Total work order akan melebihi jumlah pesanan. Pesanan %s, sudah direncanakan %s, sisa %s.',
                            number_format($order->quantity, 0, ',', '.'),
                            number_format($alreadyPlanned, 0, ',', '.'),
                            number_format(max(0, $order->quantity - $alreadyPlanned), 0, ',', '.'),
                        ),
                    ]);
                }
            }

            $workOrder = WorkOrder::create([
                'wo_number' => $this->numbering->workOrder(),
                'customer_order_id' => $data['customer_order_id'],
                'product_id' => $data['product_id'],
                'target_quantity' => $data['target_quantity'],
                'planned_date' => $data['planned_date'],
                'status' => 'dirilis',
                'notes' => $data['notes'] ?: null,
                'created_by' => $user->id,
            ]);

            $workOrder->load('product');

            $this->audit->logModel(
                $user,
                'work_order.created',
                $workOrder,
                sprintf(
                    '%s membuat work order %s — %s %s %s.',
                    $user->full_name,
                    $workOrder->wo_number,
                    number_format($workOrder->target_quantity, 0, ',', '.'),
                    $workOrder->product->unit,
                    $workOrder->product->name,
                ),
                ['wo_number' => $workOrder->wo_number, 'target' => $workOrder->target_quantity],
            );

            return $workOrder;
        });
    }

    /**
     * @param  array{work_order_id: int, user_id: int, assigned_quantity: int, planned_date: string, work_shift_id: ?int, production_stage_id: ?int, work_station_id: ?int, notes: ?string}  $data
     */
    public function createAssignment(User $planner, array $data): ProductionAssignment
    {
        abort_unless($planner->hasPermission(Permissions::ASSIGNMENT_MANAGE), 403);

        return DB::transaction(function () use ($planner, $data) {
            /** @var WorkOrder $workOrder */
            $workOrder = WorkOrder::whereKey($data['work_order_id'])->lockForUpdate()->firstOrFail();

            $unassigned = $workOrder->unassignedQuantity();

            if ($data['assigned_quantity'] > $unassigned) {
                throw ValidationException::withMessages([
                    'assignedQuantity' => sprintf(
                        'Jumlah melebihi sisa work order yang belum ditugaskan (%s %s).',
                        number_format($unassigned, 0, ',', '.'),
                        $workOrder->product->unit,
                    ),
                ]);
            }

            $operator = User::findOrFail($data['user_id']);

            // Only an operator may receive a production assignment.
            if (! $operator->hasPermission(Permissions::PRODUCTION_REPORT_CREATE)) {
                throw ValidationException::withMessages([
                    'operatorId' => 'Karyawan ini tidak memiliki izin mencatat hasil produksi.',
                ]);
            }

            $assignment = ProductionAssignment::create([
                'work_order_id' => $workOrder->id,
                'user_id' => $operator->id,
                'work_shift_id' => $data['work_shift_id'] ?: $operator->work_shift_id,
                'production_stage_id' => $data['production_stage_id'] ?: null,
                'work_station_id' => $data['work_station_id'] ?: null,
                'assigned_quantity' => $data['assigned_quantity'],
                'planned_date' => $data['planned_date'],
                'status' => ProductionAssignment::STATUS_ASSIGNED,
                'notes' => $data['notes'] ?: null,
                'created_by' => $planner->id,
            ]);

            if ($workOrder->status === 'direncanakan') {
                $workOrder->update(['status' => 'dirilis']);
            }

            $this->audit->logModel(
                $planner,
                'assignment.created',
                $assignment,
                sprintf(
                    '%s menugaskan %s pada %s — %s %s.',
                    $planner->full_name,
                    $operator->full_name,
                    $workOrder->wo_number,
                    number_format($assignment->assigned_quantity, 0, ',', '.'),
                    $workOrder->product->unit,
                ),
                [
                    'work_order' => $workOrder->wo_number,
                    'operator' => $operator->employee_number,
                    'quantity' => $assignment->assigned_quantity,
                ],
            );

            return $assignment;
        });
    }
}
