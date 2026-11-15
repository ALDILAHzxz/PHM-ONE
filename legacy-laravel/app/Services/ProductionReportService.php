<?php

namespace App\Services;

use App\Exceptions\ProductionReportException;
use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Models\ProductionReview;
use App\Models\User;
use App\Support\Permissions;
use App\Support\ReportStatus;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The single place production reports are created and reviewed.
 *
 * Everything here runs server side and inside a transaction:
 *   authenticate -> authorize -> validate business rules -> save report
 *   -> write audit event -> commit
 *
 * The Livewire form is only a client of this service; it never writes rows
 * itself, so the same rules apply no matter which interface is used.
 */
class ProductionReportService
{
    public function __construct(
        private readonly DocumentNumbering $numbering,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{quantity: int, has_issue: bool, issue_category: ?string, issue_description: ?string, additional_notes: ?string, idempotency_key: string}  $data
     *
     * @throws ProductionReportException
     */
    public function submit(User $user, ProductionAssignment $assignment, array $data): ProductionReport
    {
        // --- 1. Authorization (record level, not just role level) ------------
        if (! Gate::forUser($user)->allows('report-on-assignment', $assignment)) {
            throw $assignment->acceptsReports()
                ? ProductionReportException::notAuthorized()
                : ProductionReportException::assignmentClosed();
        }

        // --- 2. Idempotency: an identical retry returns the original row -----
        $existing = ProductionReport::where('idempotency_key', $data['idempotency_key'])->first();

        if ($existing !== null) {
            return $existing;
        }

        $quantity = (int) $data['quantity'];

        return DB::transaction(function () use ($user, $assignment, $data, $quantity) {
            // Re-read the assignment inside the transaction so two concurrent
            // submissions cannot both pass the remaining-target check.
            /** @var ProductionAssignment $locked */
            $locked = ProductionAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->acceptsReports()) {
                throw ProductionReportException::assignmentClosed();
            }

            // --- 3. Business rules -------------------------------------------
            $remaining = $locked->remainingQty();
            $isException = false;

            if ($quantity > $remaining) {
                // Documented DEMO rule (config/phm.php): reject, unless the user
                // holds the exception permission. The submitted amount is never
                // silently reduced.
                if (! $user->hasPermission(Permissions::PRODUCTION_REPORT_EXCEPTION)) {
                    throw ProductionReportException::exceedsRemaining(
                        $remaining,
                        $quantity,
                        $locked->unit(),
                    );
                }

                $isException = true;
            }

            // --- 4. Save ------------------------------------------------------
            $report = ProductionReport::create([
                'report_number' => $this->numbering->productionReport(),
                'production_assignment_id' => $locked->id,
                'user_id' => $user->id,
                'quantity_completed' => $quantity,
                'has_issue' => (bool) $data['has_issue'],
                'issue_category' => $data['has_issue'] ? $data['issue_category'] : null,
                'issue_description' => $data['has_issue'] ? $data['issue_description'] : null,
                'additional_notes' => $data['additional_notes'] ?: null,
                'status' => ReportStatus::PENDING, // never starts out verified
                'is_exception' => $isException,
                'submitted_at' => now(),
                'idempotency_key' => $data['idempotency_key'],
            ]);

            // An assignment being reported against is "berjalan", not "ditugaskan".
            if ($locked->status === ProductionAssignment::STATUS_ASSIGNED) {
                $locked->update(['status' => ProductionAssignment::STATUS_RUNNING]);
            }

            // --- 5. Audit -----------------------------------------------------
            $this->audit->logModel(
                $user,
                'production_report.created',
                $report,
                sprintf(
                    '%s mencatat %s %s pada %s (%s).',
                    $user->full_name,
                    number_format($quantity, 0, ',', '.'),
                    $locked->unit(),
                    $locked->woNumber(),
                    $locked->productName(),
                ),
                [
                    'report_number' => $report->report_number,
                    'quantity' => $quantity,
                    'has_issue' => (bool) $data['has_issue'],
                    'is_exception' => $isException,
                ],
            );

            return $report;
        });
    }

    /**
     * Verify a report, or send it back for correction.
     *
     * Verified totals only move once this transaction commits, and the original
     * submission is never edited or deleted — the review is a separate record.
     *
     * @throws ProductionReportException
     */
    public function review(
        User $reviewer,
        ProductionReport $report,
        string $decision,
        ?string $notes = null,
    ): ProductionReview {
        if (! in_array($decision, [ReportStatus::VERIFIED, ReportStatus::NEEDS_CORRECTION], true)) {
            throw new ProductionReportException('Keputusan pemeriksaan tidak dikenali.', 'decision');
        }

        if (! Gate::forUser($reviewer)->allows('review-production-report', $report)) {
            throw new ProductionReportException(
                'Anda tidak berwenang memeriksa laporan ini.',
                'decision',
            );
        }

        if ($decision === ReportStatus::NEEDS_CORRECTION && blank($notes)) {
            throw new ProductionReportException(
                'Tuliskan alasan perbaikan agar operator tahu apa yang harus diperbaiki.',
                'notes',
            );
        }

        return DB::transaction(function () use ($reviewer, $report, $decision, $notes) {
            /** @var ProductionReport $locked */
            $locked = ProductionReport::whereKey($report->id)->lockForUpdate()->firstOrFail();

            // Guard against two supervisors reviewing the same row at once.
            if ($locked->status !== ReportStatus::PENDING) {
                throw new ProductionReportException(
                    sprintf(
                        'Laporan ini sudah diperiksa (status: %s). Muat ulang daftar pemeriksaan.',
                        ReportStatus::label($locked->status),
                    ),
                    'decision',
                );
            }

            $review = ProductionReview::create([
                'production_report_id' => $locked->id,
                'reviewed_by' => $reviewer->id,
                'status' => $decision,
                'review_notes' => $notes ?: null,
                'reviewed_at' => now(),
            ]);

            $locked->update(['status' => $decision]);

            $this->audit->logModel(
                $reviewer,
                $decision === ReportStatus::VERIFIED
                    ? 'production_report.verified'
                    : 'production_report.correction_requested',
                $locked,
                sprintf(
                    '%s %s laporan %s (%s %s) dari %s.',
                    $reviewer->full_name,
                    $decision === ReportStatus::VERIFIED ? 'memverifikasi' : 'meminta perbaikan pada',
                    $locked->report_number,
                    number_format($locked->quantity_completed, 0, ',', '.'),
                    $locked->assignment?->unit() ?? 'pcs',
                    $locked->user?->full_name ?? 'operator',
                ),
                [
                    'report_number' => $locked->report_number,
                    'decision' => $decision,
                    'quantity' => $locked->quantity_completed,
                ],
            );

            return $review;
        });
    }

    /**
     * Build a fresh idempotency key for one form session. Regenerated only when
     * the operator starts a new entry, so a double-click reuses the same key.
     */
    public function newIdempotencyKey(): string
    {
        return (string) str()->uuid();
    }

    /** Translate a database-level duplicate into the worker-facing message. */
    public function isDuplicateKeyError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'idempotency_key');
    }
}
