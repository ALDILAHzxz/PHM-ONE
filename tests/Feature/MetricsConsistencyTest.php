<?php

namespace Tests\Feature;

use App\Livewire\Management\CompanyOverview;
use App\Livewire\Operator\ProductionReportForm;
use App\Livewire\Supervisor\ReviewQueue;
use App\Models\CustomerOrder;
use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Services\Beesha\BeeshaToolRegistry;
use App\Services\OperationsMetrics;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * One source of truth: the dashboard, the supervisor queue and Beesha AI all
 * read from OperationsMetrics, so a figure can never disagree between them.
 */
class MetricsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    public function test_reported_equals_pending_plus_verified(): void
    {
        $production = app(OperationsMetrics::class)->productionForDate();

        $this->assertSame(
            $production['pending'] + $production['verified'],
            $production['reported'],
        );
    }

    public function test_corrected_and_voided_reports_are_excluded_from_reported(): void
    {
        $metrics = app(OperationsMetrics::class);
        $before = $metrics->productionForDate();

        // The reported figure must equal the sum of the counted statuses only.
        $this->assertSame(
            (int) ProductionReport::query()
                ->whereDate('submitted_at', today())
                ->whereIn('status', ReportStatus::COUNTS_AS_REPORTED)
                ->sum('quantity_completed'),
            $before['reported'],
        );

        // Add one report in each excluded status, dated today.
        foreach ([ReportStatus::NEEDS_CORRECTION, ReportStatus::VOIDED] as $index => $status) {
            ProductionReport::create([
                'report_number' => 'LP-EXCLUDED-'.$index,
                'production_assignment_id' => $this->scenarioAssignment()->id,
                'user_id' => $this->operator()->id,
                'quantity_completed' => 77,
                'has_issue' => false,
                'status' => $status,
                'submitted_at' => now(),
                'idempotency_key' => 'excluded-'.$status,
            ]);
        }

        $after = $metrics->productionForDate();

        $this->assertSame(
            $before['reported'],
            $after['reported'],
            'A needs-correction or voided report must never reach the reported total.',
        );
        $this->assertSame($before['verified'], $after['verified']);
        $this->assertSame($before['pending'], $after['pending']);

        // The rows still exist — nothing silently disappeared.
        $this->assertSame(2, ProductionReport::where('quantity_completed', 77)->count());
    }

    public function test_an_excluded_report_also_leaves_the_assignment_totals_alone(): void
    {
        $assignment = $this->scenarioAssignment();
        $reportedBefore = $assignment->reportedQty();

        ProductionReport::create([
            'report_number' => 'LP-EXCLUDED-ASSIGN',
            'production_assignment_id' => $assignment->id,
            'user_id' => $this->operator()->id,
            'quantity_completed' => 40,
            'has_issue' => false,
            'status' => ReportStatus::NEEDS_CORRECTION,
            'submitted_at' => now(),
            'idempotency_key' => 'excluded-assignment',
        ]);

        $after = ProductionAssignment::withProgress()->find($assignment->id);

        $this->assertSame($reportedBefore, $after->reportedQty());
    }

    public function test_verifying_never_double_counts_a_quantity(): void
    {
        $metrics = app(OperationsMetrics::class);
        $report = ProductionReport::pending()->submittedToday()->firstOrFail();

        $before = $metrics->productionForDate();

        $this->actingAs($this->supervisor());
        Livewire::test(ReviewQueue::class)->call('verify', $report->id);

        $after = $metrics->productionForDate();

        $this->assertSame(
            $before['reported'],
            $after['reported'],
            'Reported must not change when a report is verified.',
        );
        $this->assertSame(
            $before['verified'] + $report->quantity_completed,
            $after['verified'],
        );
        $this->assertSame(
            $before['pending'] - $report->quantity_completed,
            $after['pending'],
        );
    }

    public function test_the_dashboard_and_the_assistant_report_the_same_figures(): void
    {
        $kpis = app(OperationsMetrics::class)->headlineKpis();
        $toolData = app(BeeshaToolRegistry::class)->call($this->management(), 'getAuthorizedOperationalKPIs');

        $this->assertSame($kpis['active_orders'], $toolData['pesanan_aktif']);
        $this->assertSame($kpis['reported_today'], $toolData['produksi_dilaporkan']);
        $this->assertSame($kpis['verified_today'], $toolData['produksi_terverifikasi']);
        $this->assertSame($kpis['shipments_today'], $toolData['pengiriman_hari_ini_unit']);
    }

    public function test_the_supervisor_queue_and_the_assistant_agree(): void
    {
        $this->actingAs($this->supervisor());

        $queueCount = Livewire::test(ReviewQueue::class)->instance()->pending()->count();
        $toolData = app(BeeshaToolRegistry::class)->call($this->supervisor(), 'getPendingReviews');

        $this->assertSame($queueCount, $toolData['menunggu_pemeriksaan']);
    }

    public function test_operator_screen_and_assistant_agree_on_the_remaining_target(): void
    {
        $assignment = $this->scenarioAssignment();
        $toolData = app(BeeshaToolRegistry::class)->call($this->operator(), 'getMyAssignments');

        $match = collect($toolData['items'])->firstWhere('work_order', 'DEMO-WO-004');

        $this->assertNotNull($match);
        $this->assertSame($assignment->targetQty(), $match['target']);
        $this->assertSame($assignment->reportedQty(), $match['dilaporkan']);
        $this->assertSame($assignment->verifiedQty(), $match['terverifikasi']);
        $this->assertSame($assignment->remainingQty(), $match['sisa']);
    }

    public function test_percentages_are_only_computed_when_the_target_is_positive(): void
    {
        $assignment = ProductionAssignment::withProgress()->first();
        $assignment->assigned_quantity = 0;

        $this->assertSame(0.0, $assignment->reportedProgress());
        $this->assertSame(0.0, $assignment->verifiedProgress());
    }

    public function test_remaining_never_goes_negative(): void
    {
        $assignment = $this->scenarioAssignment();
        $assignment->assigned_quantity = 100; // less than the 320 reported

        $this->assertSame(0, $assignment->remainingQty());
    }

    public function test_the_dashboard_shows_reported_and_verified_as_separate_figures(): void
    {
        $this->actingAs($this->management());

        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Produksi Dilaporkan', $html);
        $this->assertStringContainsString('Produksi Terverifikasi', $html);
        $this->assertStringContainsString('Termasuk yang belum diperiksa', $html);
        $this->assertStringContainsString('Sudah dikonfirmasi supervisor', $html);
    }

    public function test_the_dashboard_states_its_freshness(): void
    {
        $this->actingAs($this->management())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Diperbarui')
            ->assertSee('diperbarui otomatis setiap');
    }

    public function test_the_dashboard_polls_on_the_configured_interval(): void
    {
        config(['phm.poll_seconds' => 7]);

        $this->actingAs($this->management())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('wire:poll.7s', false);
    }

    public function test_a_new_report_raises_the_reported_kpi_but_not_the_verified_one(): void
    {
        $this->actingAs($this->management());
        $before = Livewire::test(CompanyOverview::class)->instance()->kpis();

        $this->actingAs($this->operator());
        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '12')
            ->call('review')
            ->call('save')
            ->assertSet('step', 'success');

        $this->actingAs($this->management());
        $after = Livewire::test(CompanyOverview::class)->instance()->kpis();

        $this->assertSame($before['reported_today'] + 12, $after['reported_today']);
        $this->assertSame($before['verified_today'], $after['verified_today']);
        $this->assertSame($before['pending_today'] + 12, $after['pending_today']);
    }

    public function test_alerts_are_derived_from_live_records(): void
    {
        $alerts = app(OperationsMetrics::class)->alerts();

        $this->assertNotEmpty($alerts, 'The demo data should produce at least one alert.');

        foreach ($alerts as $alert) {
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('title', $alert);
            $this->assertArrayHasKey('detail', $alert);
            $this->assertContains($alert['severity'], ['critical', 'warning', 'info']);
        }

        // An overdue order (DEMO-PO-006) must be flagged as critical.
        $this->assertTrue(
            $alerts->contains(fn (array $a) => str_contains($a['title'], 'DEMO-PO-006')),
        );
    }

    public function test_alerts_are_sorted_most_severe_first(): void
    {
        $severities = app(OperationsMetrics::class)->alerts()->pluck('severity')->all();

        $rank = ['critical' => 0, 'warning' => 1, 'info' => 2];
        $ranks = array_map(fn (string $s) => $rank[$s], $severities);
        $sorted = $ranks;
        sort($sorted);

        $this->assertSame($sorted, $ranks);
    }

    public function test_only_verified_production_counts_as_ready_to_ship(): void
    {
        $order = CustomerOrder::where('order_number', 'DEMO-PO-001')->firstOrFail();

        $verified = $order->verifiedProducedQuantity();
        $shipped = $order->shippedQuantity();

        $this->assertSame(
            max(0, min($order->quantity, $verified) - $shipped),
            $order->readyToShipQuantity(),
        );

        // Pending production must not inflate the ready figure.
        $this->actingAs($this->operator());
        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '50')
            ->call('review')
            ->call('save');

        $this->assertSame(
            $verified,
            $order->fresh()->verifiedProducedQuantity(),
            'A pending report must not change the verified figure.',
        );
    }
}
