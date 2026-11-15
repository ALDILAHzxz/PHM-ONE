<?php

namespace Tests\Feature;

use App\Exceptions\ProductionReportException;
use App\Livewire\Operator\ProductionReportForm;
use App\Livewire\Supervisor\ReviewQueue;
use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Models\WorkOrder;
use App\Services\OperationsMetrics;
use App\Services\ProductionReportService;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupervisorVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    public function test_supervisor_sees_the_pending_queue(): void
    {
        $this->actingAs($this->supervisor())
            ->get(route('supervisor.reviews'))
            ->assertOk()
            ->assertSee('Pemeriksaan Hari Ini')
            ->assertSee('Menunggu Pemeriksaan');
    }

    public function test_pending_queue_lists_only_pending_reports(): void
    {
        $this->actingAs($this->supervisor());

        $pending = ProductionReport::pending()->pluck('id')->sort()->values()->all();

        $listed = Livewire::test(ReviewQueue::class)
            ->instance()
            ->pending()
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($pending, $listed);
        $this->assertNotEmpty($listed, 'The demo data should leave reports awaiting review.');
    }

    public function test_verifying_a_report_moves_the_verified_total(): void
    {
        $report = ProductionReport::pending()->firstOrFail();
        $assignment = $report->assignment;

        $verifiedBefore = ProductionAssignment::withProgress()->find($assignment->id)->verifiedQty();

        $this->actingAs($this->supervisor());

        Livewire::test(ReviewQueue::class)
            ->call('verify', $report->id);

        $this->assertSame(ReportStatus::VERIFIED, $report->fresh()->status);

        $after = ProductionAssignment::withProgress()->find($assignment->id);
        $this->assertSame(
            $verifiedBefore + $report->quantity_completed,
            $after->verifiedQty(),
        );
    }

    public function test_verification_creates_a_review_record_and_audit_event(): void
    {
        $report = ProductionReport::pending()->firstOrFail();

        $this->actingAs($this->supervisor());
        Livewire::test(ReviewQueue::class)->call('verify', $report->id);

        $this->assertDatabaseHas('production_reviews', [
            'production_report_id' => $report->id,
            'reviewed_by' => $this->supervisor()->id,
            'status' => ReportStatus::VERIFIED,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'production_report.verified',
            'actor_id' => $this->supervisor()->id,
        ]);
    }

    public function test_requesting_correction_requires_a_reason(): void
    {
        $report = ProductionReport::pending()->firstOrFail();

        $this->actingAs($this->supervisor());

        Livewire::test(ReviewQueue::class)
            ->call('startCorrection', $report->id)
            ->set('correctionNotes', '')
            ->call('requestCorrection')
            ->assertHasErrors('correctionNotes');

        $this->assertSame(ReportStatus::PENDING, $report->fresh()->status);
    }

    public function test_a_corrected_report_is_excluded_from_totals_but_still_exists(): void
    {
        $report = ProductionReport::pending()->firstOrFail();
        $assignment = $report->assignment;

        $reportedBefore = ProductionAssignment::withProgress()->find($assignment->id)->reportedQty();

        $this->actingAs($this->supervisor());

        Livewire::test(ReviewQueue::class)
            ->call('startCorrection', $report->id)
            ->set('correctionNotes', 'Jumlah tidak sesuai hitung ulang di stasiun.')
            ->call('requestCorrection')
            ->assertHasNoErrors();

        $fresh = $report->fresh();

        // Still present, with its history intact.
        $this->assertSame(ReportStatus::NEEDS_CORRECTION, $fresh->status);
        $this->assertDatabaseHas('production_reports', ['id' => $report->id]);
        $this->assertSame($report->quantity_completed, $fresh->quantity_completed);

        // But no longer counted.
        $after = ProductionAssignment::withProgress()->find($assignment->id);
        $this->assertSame(
            $reportedBefore - $report->quantity_completed,
            $after->reportedQty(),
        );
        $this->assertNotSame(ReportStatus::VERIFIED, $fresh->status);
    }

    public function test_a_report_cannot_be_reviewed_twice(): void
    {
        $report = ProductionReport::pending()->firstOrFail();
        $supervisor = $this->supervisor();
        $service = app(ProductionReportService::class);

        $service->review($supervisor, $report, ReportStatus::VERIFIED);

        $this->expectException(ProductionReportException::class);
        $service->review($supervisor, $report->fresh(), ReportStatus::VERIFIED);
    }

    public function test_nobody_can_verify_their_own_report(): void
    {
        // Give the supervisor an assignment and have them report on it.
        $assignment = ProductionAssignment::create([
            'work_order_id' => WorkOrder::where('wo_number', 'DEMO-WO-004')->value('id'),
            'user_id' => $this->supervisor()->id,
            'assigned_quantity' => 50,
            'planned_date' => today(),
            'status' => ProductionAssignment::STATUS_RUNNING,
        ]);

        $report = ProductionReport::create([
            'report_number' => 'LP-TEST-0001',
            'production_assignment_id' => $assignment->id,
            'user_id' => $this->supervisor()->id,
            'quantity_completed' => 10,
            'has_issue' => false,
            'status' => ReportStatus::PENDING,
            'submitted_at' => now(),
            'idempotency_key' => 'self-review-test',
        ]);

        $this->expectException(ProductionReportException::class);

        app(ProductionReportService::class)->review(
            $this->supervisor(),
            $report,
            ReportStatus::VERIFIED,
        );
    }

    public function test_an_operator_cannot_reach_the_review_screen(): void
    {
        $this->actingAs($this->operator())
            ->get(route('supervisor.reviews'))
            ->assertForbidden();
    }

    public function test_an_operator_cannot_verify_through_the_service(): void
    {
        $report = ProductionReport::pending()->firstOrFail();

        $this->expectException(ProductionReportException::class);

        app(ProductionReportService::class)->review(
            $this->operator(),
            $report,
            ReportStatus::VERIFIED,
        );
    }

    /**
     * ==================================================================
     * The exact end-to-end case from master specification section 37.
     * ==================================================================
     */
    public function test_full_specification_scenario_operator_submits_then_supervisor_verifies(): void
    {
        $assignment = $this->scenarioAssignment();

        // --- baseline ---
        $this->assertSame(500, $assignment->targetQty());
        $this->assertSame(320, $assignment->reportedQty());
        $this->assertSame(320, $assignment->verifiedQty());

        $reportsBefore = ProductionReport::where('production_assignment_id', $assignment->id)->count();

        // --- step 3: operator submits 35 pcs ---
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '35')
            ->call('review')
            ->call('save')
            ->assertSet('step', 'success');

        // --- step 4: exactly one new report, reported moves, verified does not ---
        $this->assertSame(
            $reportsBefore + 1,
            ProductionReport::where('production_assignment_id', $assignment->id)->count(),
            'Exactly one new production report must exist.',
        );

        $afterSubmit = ProductionAssignment::withProgress()->find($assignment->id);
        $this->assertSame(355, $afterSubmit->reportedQty());
        $this->assertSame(145, $afterSubmit->remainingQty());
        $this->assertSame(320, $afterSubmit->verifiedQty());

        $newReport = ProductionReport::where('production_assignment_id', $assignment->id)
            ->latest('id')->first();
        $this->assertSame(ReportStatus::PENDING, $newReport->status);

        // Management sees the reported figure move while verified holds.
        $this->actingAs($this->management())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Produksi Dilaporkan')
            ->assertSee('Produksi Terverifikasi');

        // --- step 5: supervisor verifies the 35 pcs report ---
        $this->actingAs($this->supervisor());
        Livewire::test(ReviewQueue::class)->call('verify', $newReport->id);

        // --- step 6: verified becomes 355, with no double counting ---
        $afterVerify = ProductionAssignment::withProgress()->find($assignment->id);
        $this->assertSame(355, $afterVerify->verifiedQty(), 'Verified must become 355.');
        $this->assertSame(355, $afterVerify->reportedQty(), 'Reported must stay 355, not double to 390.');
        $this->assertSame(145, $afterVerify->remainingQty());
        $this->assertSame(0, $afterVerify->pendingQty());

        // The quantity is counted once: one row, one review.
        $this->assertSame(1, $newReport->fresh()->reviews()->count());
    }

    /**
     * Cross-session behaviour: two separate authenticated sessions observe the
     * same server state. This is what the polling dashboards re-read.
     */
    public function test_two_sessions_see_the_same_server_state(): void
    {
        $assignment = $this->scenarioAssignment();

        // Session A — management reads the dashboard metrics.
        $metricsBefore = app(OperationsMetrics::class)->headlineKpis();

        // Session B — the operator submits.
        $this->actingAs($this->operator());
        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '35')
            ->call('review')
            ->call('save');

        // Session A re-reads (what wire:poll does on the next tick).
        $metricsAfter = app(OperationsMetrics::class)->headlineKpis();

        $this->assertSame(
            $metricsBefore['reported_today'] + 35,
            $metricsAfter['reported_today'],
            'Management must see the reported total rise without re-entering anything.',
        );
        $this->assertSame(
            $metricsBefore['verified_today'],
            $metricsAfter['verified_today'],
            'Verified must not move before review.',
        );
    }
}
