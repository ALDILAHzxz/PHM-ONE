<?php

namespace App\Models;

use App\Support\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    protected $fillable = [
        'wo_number',
        'customer_order_id',
        'product_id',
        'target_quantity',
        'planned_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'target_quantity' => 'integer',
        ];
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProductionAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['direncanakan', 'dirilis', 'berjalan']);
    }

    /** Sum of report quantities counted as reported for this whole work order. */
    public function reportedQuantity(): int
    {
        return (int) ProductionReport::query()
            ->whereIn('status', ReportStatus::COUNTS_AS_REPORTED)
            ->whereHas('assignment', fn (Builder $q) => $q->where('work_order_id', $this->id))
            ->sum('quantity_completed');
    }

    public function verifiedQuantity(): int
    {
        return (int) ProductionReport::query()
            ->whereIn('status', ReportStatus::COUNTS_AS_VERIFIED)
            ->whereHas('assignment', fn (Builder $q) => $q->where('work_order_id', $this->id))
            ->sum('quantity_completed');
    }

    /** Quantity already handed out to operators through assignments. */
    public function assignedQuantity(): int
    {
        return (int) $this->assignments()
            ->whereNot('status', 'dibatalkan')
            ->sum('assigned_quantity');
    }

    public function unassignedQuantity(): int
    {
        return max(0, $this->target_quantity - $this->assignedQuantity());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'direncanakan' => 'Direncanakan',
            'dirilis' => 'Dirilis',
            'berjalan' => 'Berjalan',
            'selesai' => 'Selesai',
            'ditutup' => 'Ditutup',
            default => $this->status,
        };
    }
}
