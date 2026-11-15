<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\Permissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly DocumentNumbering $numbering,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{partner_id: int, po_number: string, product_id: int, quantity: int, delivery_target_date: string, notes: ?string}  $data
     */
    public function create(User $user, array $data): CustomerOrder
    {
        abort_unless($user->hasPermission(Permissions::ORDER_CREATE), 403);

        return DB::transaction(function () use ($user, $data) {
            // The (partner_id, po_number) unique index is the real guard; this
            // check exists to turn it into a readable message.
            $duplicate = CustomerOrder::where('partner_id', $data['partner_id'])
                ->where('po_number', $data['po_number'])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'po_number' => 'Nomor PO ini sudah tercatat untuk mitra tersebut.',
                ]);
            }

            $order = CustomerOrder::create([
                'order_number' => $this->numbering->customerOrder(),
                'partner_id' => $data['partner_id'],
                'po_number' => $data['po_number'],
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'delivery_target_date' => $data['delivery_target_date'],
                'status' => OrderStatus::NEW,
                'notes' => $data['notes'] ?: null,
                'created_by' => $user->id,
            ]);

            $order->load(['partner', 'product']);

            $this->audit->logModel(
                $user,
                'customer_order.created',
                $order,
                sprintf(
                    '%s menambahkan pesanan %s dari %s — %s %s %s.',
                    $user->full_name,
                    $order->order_number,
                    $order->partner->name,
                    number_format($order->quantity, 0, ',', '.'),
                    $order->product->unit,
                    $order->product->name,
                ),
                [
                    'order_number' => $order->order_number,
                    'po_number' => $order->po_number,
                    'quantity' => $order->quantity,
                ],
            );

            return $order;
        });
    }

    /** Recalculate an order's fulfilment status from its shipments. */
    public function refreshFulfilmentStatus(CustomerOrder $order): CustomerOrder
    {
        if (in_array($order->status, [OrderStatus::CANCELLED, OrderStatus::COMPLETED], true)
            && $order->shippedQuantity() >= $order->quantity) {
            return $order;
        }

        $shipped = $order->shippedQuantity();

        $status = match (true) {
            $shipped >= $order->quantity => OrderStatus::COMPLETED,
            $shipped > 0 => OrderStatus::PARTIALLY_SHIPPED,
            $order->readyToShipQuantity() >= $order->quantity => OrderStatus::READY,
            $order->verifiedProducedQuantity() > 0 => OrderStatus::IN_PRODUCTION,
            default => $order->status,
        };

        if ($status !== $order->status && $order->status !== OrderStatus::CANCELLED) {
            $order->update(['status' => $status]);
        }

        return $order;
    }
}
