<?php

namespace App\Models;

use App\Support\OrderStatus;
use App\Support\ReportStatus;
use App\Support\ShipmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrder extends Model
{
    protected $fillable = [
        'order_number',
        'partner_id',
        'po_number',
        'product_id',
        'quantity',
        'delivery_target_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_target_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    // ---------------------------------------------------------------- relations

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ------------------------------------------------------------------ scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::ACTIVE);
    }

    /** Orders whose delivery target falls within the next $days days. */
    public function scopeApproachingDeadline(Builder $query, int $days = 7): Builder
    {
        return $query->active()
            ->whereDate('delivery_target_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('delivery_target_date');
    }

    // ---------------------------------------------------------------- quantities

    /** Units that have physically left the factory (dikirim/tiba/diterima). */
    public function shippedQuantity(): int
    {
        return (int) $this->shipments()
            ->whereIn('status', ShipmentStatus::COUNTS_AS_SHIPPED)
            ->sum('quantity_shipped');
    }

    /**
     * Units verified by a supervisor across this order's work orders.
     * Only verified production is treated as available to ship.
     */
    public function verifiedProducedQuantity(): int
    {
        return (int) ProductionReport::query()
            ->whereIn('status', ReportStatus::COUNTS_AS_VERIFIED)
            ->whereHas('assignment.workOrder', fn (Builder $q) => $q->where('customer_order_id', $this->id))
            ->sum('quantity_completed');
    }

    /** Verified production not yet shipped. */
    public function readyToShipQuantity(): int
    {
        return max(0, min($this->quantity, $this->verifiedProducedQuantity()) - $this->shippedQuantity());
    }

    public function remainingToShipQuantity(): int
    {
        return max(0, $this->quantity - $this->shippedQuantity());
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, OrderStatus::ACTIVE, true)
            && $this->delivery_target_date->isBefore(today());
    }

    public function daysToDeadline(): int
    {
        return today()->diffInDays($this->delivery_target_date, false);
    }

    public function statusLabel(): string
    {
        return OrderStatus::label($this->status);
    }

    public function statusBadgeClasses(): string
    {
        return OrderStatus::badgeClasses($this->status);
    }
}
