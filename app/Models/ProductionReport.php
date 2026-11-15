<?php

namespace App\Models;

use App\Support\IssueCategory;
use App\Support\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductionReport extends Model
{
    protected $fillable = [
        'report_number',
        'production_assignment_id',
        'user_id',
        'quantity_completed',
        'has_issue',
        'issue_category',
        'issue_description',
        'additional_notes',
        'status',
        'is_exception',
        'submitted_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'has_issue' => 'boolean',
            'is_exception' => 'boolean',
            'quantity_completed' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- relations

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ProductionAssignment::class, 'production_assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductionReview::class);
    }

    public function latestReview(): HasOne
    {
        return $this->hasOne(ProductionReview::class)->latestOfMany('reviewed_at');
    }

    // ------------------------------------------------------------------ scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::PENDING);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::VERIFIED);
    }

    public function scopeNeedsCorrection(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::NEEDS_CORRECTION);
    }

    public function scopeCountsAsReported(Builder $query): Builder
    {
        return $query->whereIn('status', ReportStatus::COUNTS_AS_REPORTED);
    }

    public function scopeSubmittedToday(Builder $query): Builder
    {
        return $query->whereDate('submitted_at', today());
    }

    public function scopeWithIssue(Builder $query): Builder
    {
        return $query->where('has_issue', true);
    }

    // ----------------------------------------------------------------- display

    public function statusLabel(): string
    {
        return ReportStatus::label($this->status);
    }

    public function statusBadgeClasses(): string
    {
        return ReportStatus::badgeClasses($this->status);
    }

    public function issueLabel(): string
    {
        return $this->has_issue ? IssueCategory::label($this->issue_category) : 'Tidak ada';
    }

    public function isPending(): bool
    {
        return $this->status === ReportStatus::PENDING;
    }
}
