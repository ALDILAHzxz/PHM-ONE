<?php

namespace App\Models;

use App\Support\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryStatusEvent extends Model
{
    protected $fillable = [
        'shipment_id',
        'status',
        'actor_id',
        'recipient_name',
        'notes',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function statusLabel(): string
    {
        return ShipmentStatus::label($this->status);
    }
}
