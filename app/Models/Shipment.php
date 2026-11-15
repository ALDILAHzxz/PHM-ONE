<?php

namespace App\Models;

use App\Support\ShipmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'shipment_number',
        'customer_order_id',
        'quantity_shipped',
        'delivery_note_number',
        'driver_id',
        'destination',
        'status',
        'notes',
        'shipped_at',
        'delivered_at',
        'recorded_by',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'quantity_shipped' => 'integer',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- relations

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryStatusEvent::class)->orderByDesc('occurred_at');
    }

    // ------------------------------------------------------------------ scopes

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShipmentStatus::PREPARED,
            ShipmentStatus::IN_TRANSIT,
            ShipmentStatus::ARRIVED,
            ShipmentStatus::ISSUE,
        ]);
    }

    public function scopeForDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeShippedToday(Builder $query): Builder
    {
        return $query->whereDate('shipped_at', today());
    }

    // ----------------------------------------------------------------- status

    /** @return array<int, string> */
    public function allowedNextStatuses(): array
    {
        return ShipmentStatus::nextStatuses($this->status);
    }

    public function statusLabel(): string
    {
        return ShipmentStatus::label($this->status);
    }

    public function statusBadgeClasses(): string
    {
        return ShipmentStatus::badgeClasses($this->status);
    }

    public function countsAsShipped(): bool
    {
        return in_array($this->status, ShipmentStatus::COUNTS_AS_SHIPPED, true);
    }
}
