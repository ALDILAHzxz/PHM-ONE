<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\DeliveryStatusEvent;
use App\Models\Shipment;
use App\Models\User;
use App\Support\Permissions;
use App\Support\ShipmentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function __construct(
        private readonly DocumentNumbering $numbering,
        private readonly AuditLogger $audit,
        private readonly OrderService $orders,
    ) {}

    /**
     * @param  array{customer_order_id: int, quantity: int, delivery_note_number: ?string, driver_id: ?int, notes: ?string, idempotency_key: string}  $data
     */
    public function record(User $user, array $data): Shipment
    {
        abort_unless($user->hasPermission(Permissions::SHIPMENT_CREATE), 403);

        // A retried submission returns the original record instead of a second one.
        $existing = Shipment::where('idempotency_key', $data['idempotency_key'])->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $data) {
            /** @var CustomerOrder $order */
            $order = CustomerOrder::whereKey($data['customer_order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $order->load(['partner', 'product']);

            $remaining = $order->remainingToShipQuantity();

            if ($data['quantity'] > $remaining) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Jumlah melebihi sisa yang belum dikirim (%s %s dari pesanan %s %s).',
                        number_format($remaining, 0, ',', '.'),
                        $order->product->unit,
                        number_format($order->quantity, 0, ',', '.'),
                        $order->product->unit,
                    ),
                ]);
            }

            // Only verified production may be shipped: pending reports are not
            // treated as goods that exist.
            $ready = $order->readyToShipQuantity();

            if ($data['quantity'] > $ready) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Barang siap kirim baru %s %s (produksi terverifikasi dikurangi yang sudah dikirim). '
                        .'Minta supervisor memverifikasi laporan produksi terlebih dahulu.',
                        number_format($ready, 0, ',', '.'),
                        $order->product->unit,
                    ),
                ]);
            }

            $shipment = Shipment::create([
                'shipment_number' => $this->numbering->shipment(),
                'customer_order_id' => $order->id,
                'quantity_shipped' => $data['quantity'],
                'delivery_note_number' => $data['delivery_note_number'] ?: $this->numbering->deliveryNote(),
                'driver_id' => $data['driver_id'] ?: null,
                'destination' => $order->partner->address,
                'status' => ShipmentStatus::PREPARED,
                'notes' => $data['notes'] ?: null,
                'recorded_by' => $user->id,
                'idempotency_key' => $data['idempotency_key'],
            ]);

            DeliveryStatusEvent::create([
                'shipment_id' => $shipment->id,
                'status' => ShipmentStatus::PREPARED,
                'actor_id' => $user->id,
                'occurred_at' => now(),
            ]);

            $this->orders->refreshFulfilmentStatus($order);

            $this->audit->logModel(
                $user,
                'shipment.created',
                $shipment,
                sprintf(
                    '%s mencatat pengiriman %s untuk %s — %s %s.',
                    $user->full_name,
                    $shipment->shipment_number,
                    $order->partner->name,
                    number_format($shipment->quantity_shipped, 0, ',', '.'),
                    $order->product->unit,
                ),
                [
                    'shipment_number' => $shipment->shipment_number,
                    'order_number' => $order->order_number,
                    'quantity' => $shipment->quantity_shipped,
                ],
            );

            return $shipment;
        });
    }

    /**
     * Move a shipment to its next state. Invalid transitions are refused, so
     * contradictory delivery statuses cannot be recorded.
     */
    public function updateStatus(
        User $user,
        Shipment $shipment,
        string $newStatus,
        ?string $notes = null,
        ?string $recipientName = null,
    ): Shipment {
        if (! Gate::forUser($user)->allows('update-shipment-status', $shipment)) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak berwenang memperbarui pengiriman ini.',
            ]);
        }

        if ($newStatus === ShipmentStatus::ISSUE && blank($notes)) {
            throw ValidationException::withMessages([
                'notes' => 'Jelaskan kendala yang terjadi agar bisa ditindaklanjuti.',
            ]);
        }

        return DB::transaction(function () use ($user, $shipment, $newStatus, $notes, $recipientName) {
            /** @var Shipment $locked */
            $locked = Shipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();

            if (! ShipmentStatus::canTransition($locked->status, $newStatus)) {
                throw ValidationException::withMessages([
                    'status' => sprintf(
                        'Status tidak dapat diubah dari "%s" ke "%s". Muat ulang halaman untuk melihat status terbaru.',
                        ShipmentStatus::label($locked->status),
                        ShipmentStatus::label($newStatus),
                    ),
                ]);
            }

            $attributes = ['status' => $newStatus];

            if ($newStatus === ShipmentStatus::IN_TRANSIT && $locked->shipped_at === null) {
                $attributes['shipped_at'] = now();
            }

            if ($newStatus === ShipmentStatus::RECEIVED) {
                $attributes['delivered_at'] = now();
            }

            if (filled($notes)) {
                $attributes['notes'] = $notes;
            }

            $locked->update($attributes);

            DeliveryStatusEvent::create([
                'shipment_id' => $locked->id,
                'status' => $newStatus,
                'actor_id' => $user->id,
                'recipient_name' => $recipientName ?: null,
                'notes' => $notes ?: null,
                'occurred_at' => now(),
            ]);

            if (in_array($newStatus, ShipmentStatus::COUNTS_AS_SHIPPED, true)) {
                $this->orders->refreshFulfilmentStatus($locked->order);
            }

            $this->audit->logModel(
                $user,
                'shipment.status_changed',
                $locked,
                sprintf(
                    '%s mengubah status pengiriman %s menjadi %s.',
                    $user->full_name,
                    $locked->shipment_number,
                    ShipmentStatus::label($newStatus),
                ),
                ['shipment_number' => $locked->shipment_number, 'status' => $newStatus],
            );

            return $locked;
        });
    }

    public function newIdempotencyKey(): string
    {
        return (string) str()->uuid();
    }
}
