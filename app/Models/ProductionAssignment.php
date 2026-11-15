<?php

namespace App\Models;

use App\Support\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A unit of work handed to one operator. All progress numbers are derived from
 * production_reports on read (see App\Support\ReportStatus) — never stored as a
 * running total — so a report can be verified or sent back for correction
 * without any risk of double counting.
 */
class ProductionAssignment extends Model
{
    public const STATUS_ASSIGNED = 'ditugaskan';

    public const STATUS_RUNNING = 'berjalan';

    public const STATUS_DONE = 'selesai';

    public const STATUS_CLOSED = 'ditutup';

    public const STATUS_CANCELLED = 'dibatalkan';

    /** Statuses that still accept new production reports. */
    public const OPEN_STATUSES = [self::STATUS_ASSIGNED, self::STATUS_RUNNING];

    protected $fillable = [
        'work_order_id',
        'user_id',
        'work_shift_id',
        'production_stage_id',
        'work_station_id',
        'assigned_quantity',
        'planned_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'assigned_quantity' => 'integer',
        ];
    }

    // ---------------------------------------------------------------- relations

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProductionStage::class, 'production_stage_id');
    }

    public function workStation(): BelongsTo
    {
        return $this->belongsTo(WorkStation::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ProductionReport::class);
    }

    // ------------------------------------------------------------------ scopes

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Eager-load the two sums every progress read needs, so assignment lists
     * do not fire one query per row.
     */
    public function scopeWithProgress(Builder $query): Builder
    {
        return $query
            ->withSum(
                ['reports as reported_sum' => fn (Builder $q) => $q->whereIn('status', ReportStatus::COUNTS_AS_REPORTED)],
                'quantity_completed'
            )
            ->withSum(
                ['reports as verified_sum' => fn (Builder $q) => $q->whereIn('status', ReportStatus::COUNTS_AS_VERIFIED)],
                'quantity_completed'
            )
            ->withSum(
                ['reports as pending_sum' => fn (Builder $q) => $q->where('status', ReportStatus::PENDING)],
                'quantity_completed'
            );
    }

    // -------------------------------------------------------------- quantities

    public function targetQty(): int
    {
        return (int) $this->assigned_quantity;
    }

    /** Units reported by the operator (pending review + verified). */
    public function reportedQty(): int
    {
        if ($this->reported_sum !== null) {
            return (int) $this->reported_sum;
        }

        return (int) $this->reports()
            ->whereIn('status', ReportStatus::COUNTS_AS_REPORTED)
            ->sum('quantity_completed');
    }

    /** Units a supervisor has confirmed. */
    public function verifiedQty(): int
    {
        if ($this->verified_sum !== null) {
            return (int) $this->verified_sum;
        }

        return (int) $this->reports()
            ->whereIn('status', ReportStatus::COUNTS_AS_VERIFIED)
            ->sum('quantity_completed');
    }

    /** Units reported but not yet reviewed. */
    public function pendingQty(): int
    {
        if ($this->pending_sum !== null) {
            return (int) $this->pending_sum;
        }

        return (int) $this->reports()
            ->where('status', ReportStatus::PENDING)
            ->sum('quantity_completed');
    }

    /** remainingQty = max(0, targetQty - reportedQty) */
    public function remainingQty(): int
    {
        return max(0, $this->targetQty() - $this->reportedQty());
    }

    public function reportedProgress(): float
    {
        return $this->targetQty() > 0
            ? round($this->reportedQty() / $this->targetQty() * 100, 1)
            : 0.0;
    }

    public function verifiedProgress(): float
    {
        return $this->targetQty() > 0
            ? round($this->verifiedQty() / $this->targetQty() * 100, 1)
            : 0.0;
    }

    // ----------------------------------------------------------------- status

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function acceptsReports(): bool
    {
        return $this->isOpen();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ASSIGNED => 'Ditugaskan',
            self::STATUS_RUNNING => 'Berjalan',
            self::STATUS_DONE => 'Selesai',
            self::STATUS_CLOSED => 'Ditutup',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => $this->status,
        };
    }

    public function productName(): string
    {
        return $this->workOrder?->product?->name ?? 'Produk tidak diketahui';
    }

    public function unit(): string
    {
        return $this->workOrder?->product?->unit ?? 'pcs';
    }

    public function woNumber(): string
    {
        return $this->workOrder?->wo_number ?? '—';
    }
}
