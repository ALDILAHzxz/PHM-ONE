<?php

namespace Tests\Feature;

use App\Exceptions\ProductionReportException;
use App\Livewire\Operator\ProductionReportForm;
use App\Models\Permission;
use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Services\ProductionReportService;
use App\Support\IssueCategory;
use App\Support\Permissions;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    // ------------------------------------------------------------- baseline

    /** Specification section 37 starting state. */
    public function test_demo_baseline_matches_the_specification_scenario(): void
    {
        $assignment = $this->scenarioAssignment();

        $this->assertSame('Knalpot Standar Motor A', $assignment->productName());
        $this->assertSame(500, $assignment->targetQty());
        $this->assertSame(320, $assignment->reportedQty());
        $this->assertSame(320, $assignment->verifiedQty());
        $this->assertSame(180, $assignment->remainingQty());
        $this->assertSame(0, $assignment->pendingQty());
    }

    public function test_operator_sees_their_assignment_on_tugas_saya(): void
    {
        $this->actingAs($this->operator())
            ->get(route('operator.tasks'))
            ->assertOk()
            ->assertSee('Tugas Saya')
            ->assertSee('Knalpot Standar Motor A')
            ->assertSee('DEMO-WO-004')
            ->assertSee('Catat Hasil Kerja');
    }

    // ------------------------------------------------------------ validation

    public function test_quantity_is_required(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '')
            ->call('review')
            ->assertHasErrors(['quantity' => 'required'])
            ->assertSet('step', 'form');
    }

    public function test_quantity_must_be_greater_than_zero(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '0')
            ->call('review')
            ->assertHasErrors(['quantity' => 'min']);
    }

    public function test_negative_quantity_is_rejected(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '-15')
            ->call('review')
            ->assertHasErrors('quantity');
    }

    public function test_decimal_quantity_is_rejected(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '12.5')
            ->call('review')
            ->assertHasErrors(['quantity' => 'integer']);
    }

    public function test_issue_category_and_description_are_required_when_an_issue_is_reported(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '20')
            ->set('issueState', 'ada')
            ->call('review')
            ->assertHasErrors(['issueCategory', 'issueDescription'])
            ->assertSet('step', 'form');
    }

    public function test_no_issue_fields_are_required_when_there_is_no_issue(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '20')
            ->set('issueState', 'tidak')
            ->call('review')
            ->assertHasNoErrors()
            ->assertSet('step', 'confirm');
    }

    public function test_switching_back_to_no_issue_clears_the_conditional_fields(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('issueState', 'ada')
            ->set('issueCategory', IssueCategory::MACHINE)
            ->set('issueDescription', 'Mesin las mati 30 menit.')
            ->set('issueState', 'tidak')
            ->assertSet('issueCategory', '')
            ->assertSet('issueDescription', '');
    }

    public function test_invalid_submission_preserves_entered_values(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '0')
            ->set('additionalNotes', 'Catatan penting yang tidak boleh hilang.')
            ->call('review')
            ->assertHasErrors('quantity')
            ->assertSet('additionalNotes', 'Catatan penting yang tidak boleh hilang.');
    }

    // ------------------------------------------------------------ submission

    public function test_confirmation_step_does_not_save_anything(): void
    {
        $this->actingAs($this->operator());
        $before = ProductionReport::count();

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '35')
            ->call('review')
            ->assertSet('step', 'confirm');

        $this->assertSame($before, ProductionReport::count(), 'Review must not persist a report.');
    }

    /** The full section 37 step 3 and step 4. */
    public function test_operator_submits_thirty_five_units_and_totals_move_correctly(): void
    {
        $this->actingAs($this->operator());
        $assignment = $this->scenarioAssignment();

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '35')
            ->call('review')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('step', 'success');

        $fresh = ProductionAssignment::withProgress()->find($assignment->id);

        $this->assertSame(355, $fresh->reportedQty(), 'Reported must become 355.');
        $this->assertSame(320, $fresh->verifiedQty(), 'Verified must stay 320 until review.');
        $this->assertSame(145, $fresh->remainingQty(), 'Remaining must become 145.');
        $this->assertSame(35, $fresh->pendingQty(), 'The new 35 pcs must be pending.');

        $report = ProductionReport::latest('id')->first();
        $this->assertSame(ReportStatus::PENDING, $report->status);
        $this->assertSame(35, $report->quantity_completed);
        $this->assertNotNull($report->report_number);
        $this->assertNotNull($report->submitted_at);
    }

    public function test_submission_writes_an_audit_event(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '25')
            ->call('review')
            ->call('save');

        $this->assertDatabaseHas('audit_events', [
            'action' => 'production_report.created',
            'actor_id' => $this->operator()->id,
        ]);
    }

    public function test_an_assignment_moves_to_running_once_reported_against(): void
    {
        $operator = $this->operator();
        $this->actingAs($operator);

        $assignment = ProductionAssignment::where('user_id', $operator->id)
            ->where('status', ProductionAssignment::STATUS_RUNNING)
            ->firstOrFail();

        $assignment->update(['status' => ProductionAssignment::STATUS_ASSIGNED]);

        app(ProductionReportService::class)->submit($operator, $assignment, [
            'quantity' => 10,
            'has_issue' => false,
            'issue_category' => null,
            'issue_description' => null,
            'additional_notes' => null,
            'idempotency_key' => 'test-status-flip',
        ]);

        $this->assertSame(
            ProductionAssignment::STATUS_RUNNING,
            $assignment->fresh()->status,
        );
    }

    // ----------------------------------------------------------- idempotency

    public function test_reusing_an_idempotency_key_does_not_create_a_second_report(): void
    {
        $operator = $this->operator();
        $assignment = $this->scenarioAssignment();
        $service = app(ProductionReportService::class);

        $payload = [
            'quantity' => 30,
            'has_issue' => false,
            'issue_category' => null,
            'issue_description' => null,
            'additional_notes' => null,
            'idempotency_key' => 'double-click-key',
        ];

        $first = $service->submit($operator, $assignment, $payload);
        $second = $service->submit($operator, $assignment, $payload);

        $this->assertSame($first->id, $second->id, 'A retry must return the original report.');
        $this->assertSame(1, ProductionReport::where('idempotency_key', 'double-click-key')->count());
        $this->assertSame(350, ProductionAssignment::withProgress()->find($assignment->id)->reportedQty());
    }

    public function test_calling_save_twice_from_the_form_records_one_report(): void
    {
        $this->actingAs($this->operator());
        $before = ProductionReport::count();

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '40')
            ->call('review')
            ->call('save')
            ->call('save');

        $this->assertSame($before + 1, ProductionReport::count());
    }

    // ------------------------------------------------- authorization & limits

    public function test_an_operator_cannot_report_on_someone_elses_assignment(): void
    {
        $other = $this->demoUser('PHM-EMP-010');
        $otherAssignment = ProductionAssignment::where('user_id', $other->id)->open()->firstOrFail();

        $this->actingAs($this->operator())
            ->get(route('operator.report.create', $otherAssignment))
            ->assertForbidden();
    }

    public function test_the_service_refuses_an_assignment_belonging_to_another_operator(): void
    {
        $other = $this->demoUser('PHM-EMP-010');
        $otherAssignment = ProductionAssignment::where('user_id', $other->id)->open()->firstOrFail();

        $this->expectException(ProductionReportException::class);

        app(ProductionReportService::class)->submit($this->operator(), $otherAssignment, [
            'quantity' => 5,
            'has_issue' => false,
            'issue_category' => null,
            'issue_description' => null,
            'additional_notes' => null,
            'idempotency_key' => 'cross-operator',
        ]);
    }

    public function test_a_closed_assignment_cannot_accept_new_reports(): void
    {
        $operator = $this->operator();
        $assignment = $this->scenarioAssignment();
        $assignment->update(['status' => ProductionAssignment::STATUS_CLOSED]);

        $this->expectException(ProductionReportException::class);

        app(ProductionReportService::class)->submit($operator, $assignment->fresh(), [
            'quantity' => 5,
            'has_issue' => false,
            'issue_category' => null,
            'issue_description' => null,
            'additional_notes' => null,
            'idempotency_key' => 'closed-assignment',
        ]);
    }

    /**
     * Documented demo overproduction rule: rejected, never silently reduced.
     */
    public function test_quantity_above_the_remaining_target_is_rejected_not_truncated(): void
    {
        $this->actingAs($this->operator());
        $assignment = $this->scenarioAssignment();

        $this->assertSame(180, $assignment->remainingQty());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '500')
            ->call('review')
            ->call('save')
            ->assertHasErrors('quantity')
            ->assertSet('step', 'form');

        $fresh = ProductionAssignment::withProgress()->find($assignment->id);
        $this->assertSame(320, $fresh->reportedQty(), 'Nothing may be recorded.');
    }

    public function test_the_exact_remaining_quantity_is_accepted(): void
    {
        $this->actingAs($this->operator());
        $assignment = $this->scenarioAssignment();

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', (string) $assignment->remainingQty())
            ->call('review')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('step', 'success');

        $fresh = ProductionAssignment::withProgress()->find($assignment->id);
        $this->assertSame(500, $fresh->reportedQty());
        $this->assertSame(0, $fresh->remainingQty());
    }

    public function test_a_user_with_the_exception_permission_may_exceed_the_remaining_target(): void
    {
        $operator = $this->operator();

        // Grant the documented exception permission to this operator's role.
        $role = $operator->roles->first();
        $permission = Permission::where('code', Permissions::PRODUCTION_REPORT_EXCEPTION)
            ->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $report = app(ProductionReportService::class)->submit(
            $operator->fresh(['roles.permissions']),
            $this->scenarioAssignment(),
            [
                'quantity' => 250, // remaining is 180
                'has_issue' => false,
                'issue_category' => null,
                'issue_description' => null,
                'additional_notes' => null,
                'idempotency_key' => 'exception-key',
            ],
        );

        $this->assertTrue($report->is_exception, 'The report must be flagged as an exception.');
        $this->assertSame(250, $report->quantity_completed, 'The amount must not be reduced.');
    }

    // ------------------------------------------------------------ history

    public function test_operator_history_only_shows_their_own_reports(): void
    {
        $otherOperatorName = $this->demoUser('PHM-EMP-010')->full_name;

        $this->actingAs($this->operator())
            ->get(route('operator.history'))
            ->assertOk()
            ->assertSee('Riwayat Saya')
            ->assertDontSee($otherOperatorName);
    }

    public function test_issue_details_are_stored_with_the_report(): void
    {
        $this->actingAs($this->operator());

        Livewire::test(ProductionReportForm::class)
            ->set('quantity', '18')
            ->set('issueState', 'ada')
            ->set('issueCategory', IssueCategory::MATERIAL)
            ->set('issueDescription', 'Bahan plat datang terlambat dari gudang.')
            ->call('review')
            ->call('save')
            ->assertHasNoErrors();

        $report = ProductionReport::latest('id')->first();

        $this->assertTrue($report->has_issue);
        $this->assertSame(IssueCategory::MATERIAL, $report->issue_category);
        $this->assertSame('Bahan plat datang terlambat dari gudang.', $report->issue_description);
    }
}
