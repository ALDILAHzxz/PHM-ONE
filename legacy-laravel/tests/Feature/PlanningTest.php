<?php

namespace Tests\Feature;

use App\Livewire\Operator\TaskList;
use App\Livewire\Planning\AssignmentManager;
use App\Livewire\Planning\WorkOrderManager;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\ProductionAssignment;
use App\Models\ProductionStage;
use App\Models\WorkOrder;
use App\Services\PlanningService;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    public function test_ppic_sees_the_planning_overview(): void
    {
        $this->actingAs($this->ppic())
            ->get(route('planning.index'))
            ->assertOk()
            ->assertSee('Rencana Produksi')
            ->assertSee('Pesanan Yang Perlu Direncanakan');
    }

    public function test_a_work_order_can_be_created_from_an_order(): void
    {
        $this->actingAs($this->ppic());

        $order = CustomerOrder::where('order_number', 'DEMO-PO-004')->firstOrFail();
        $alreadyPlanned = (int) $order->workOrders()->sum('target_quantity');
        $remaining = $order->quantity - $alreadyPlanned;

        Livewire::test(WorkOrderManager::class)
            ->set('customerOrderId', $order->id)
            ->set('targetQuantity', (string) min(200, $remaining))
            ->set('plannedDate', today()->addDay()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $wo = WorkOrder::where('customer_order_id', $order->id)->latest('id')->first();

        $this->assertNotNull($wo);
        $this->assertSame($order->product_id, $wo->product_id, 'Product is filled from the order.');
        $this->assertSame($this->ppic()->id, $wo->created_by);

        $this->assertDatabaseHas('audit_events', ['action' => 'work_order.created']);
    }

    public function test_selecting_an_order_auto_fills_the_product_and_remaining_quantity(): void
    {
        $this->actingAs($this->ppic());

        $order = CustomerOrder::where('order_number', 'DEMO-PO-004')->firstOrFail();

        // DEMO-PO-004 already has DEMO-WO-008 planned, so the form should
        // suggest only what is still unplanned, not the whole order.
        $alreadyPlanned = (int) $order->workOrders()->sum('target_quantity');
        $this->assertGreaterThan(0, $alreadyPlanned);

        Livewire::test(WorkOrderManager::class)
            ->set('customerOrderId', $order->id)
            ->assertSet('productId', $order->product_id)
            ->assertSet('targetQuantity', (string) ($order->quantity - $alreadyPlanned));
    }

    public function test_work_orders_cannot_exceed_the_ordered_quantity(): void
    {
        $order = CustomerOrder::where('order_number', 'DEMO-PO-001')->firstOrFail();

        $this->expectException(ValidationException::class);

        app(PlanningService::class)->createWorkOrder($this->ppic(), [
            'customer_order_id' => $order->id,
            'product_id' => $order->product_id,
            'target_quantity' => $order->quantity + 1,
            'planned_date' => today()->toDateString(),
            'notes' => null,
        ]);
    }

    public function test_an_assignment_cannot_exceed_the_unassigned_work_order_quantity(): void
    {
        $wo = WorkOrder::where('wo_number', 'DEMO-WO-004')->firstOrFail();
        $unassigned = $wo->unassignedQuantity();

        $this->expectException(ValidationException::class);

        app(PlanningService::class)->createAssignment($this->ppic(), [
            'work_order_id' => $wo->id,
            'user_id' => $this->demoUser('PHM-EMP-010')->id,
            'assigned_quantity' => $unassigned + 100,
            'planned_date' => today()->toDateString(),
            'work_shift_id' => null,
            'production_stage_id' => null,
            'work_station_id' => null,
            'notes' => null,
        ]);
    }

    public function test_only_a_user_who_can_report_production_may_be_assigned(): void
    {
        $wo = WorkOrder::where('wo_number', 'DEMO-WO-008')->firstOrFail();

        $this->expectException(ValidationException::class);

        app(PlanningService::class)->createAssignment($this->ppic(), [
            'work_order_id' => $wo->id,
            'user_id' => $this->management()->id,   // management cannot report
            'assigned_quantity' => 10,
            'planned_date' => today()->toDateString(),
            'work_shift_id' => null,
            'production_stage_id' => null,
            'work_station_id' => null,
            'notes' => null,
        ]);
    }

    /** A new assignment must show up on the operator's own screen. */
    public function test_a_new_assignment_appears_on_the_operators_task_list(): void
    {
        $operator = $this->demoUser('PHM-EMP-011');
        $wo = WorkOrder::where('wo_number', 'DEMO-WO-008')->firstOrFail();

        app(PlanningService::class)->createAssignment($this->ppic(), [
            'work_order_id' => $wo->id,
            'user_id' => $operator->id,
            'assigned_quantity' => 120,
            'planned_date' => today()->toDateString(),
            'work_shift_id' => null,
            'production_stage_id' => null,
            'work_station_id' => null,
            'notes' => null,
        ]);

        $visible = Livewire::actingAs($operator)
            ->test(TaskList::class)
            ->instance()
            ->assignments();

        $this->assertTrue(
            $visible->contains(fn (ProductionAssignment $a) => $a->woNumber() === 'DEMO-WO-008'),
            'The operator must see the newly assigned work.',
        );
    }

    public function test_an_assignment_can_be_created_through_the_livewire_form(): void
    {
        $this->actingAs($this->ppic());

        $wo = WorkOrder::where('wo_number', 'DEMO-WO-008')->firstOrFail();
        $operator = $this->demoUser('PHM-EMP-012');

        Livewire::test(AssignmentManager::class)
            ->set('workOrderId', $wo->id)
            ->set('operatorId', $operator->id)
            ->set('assignedQuantity', '50')
            ->set('plannedDate', today()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('production_assignments', [
            'work_order_id' => $wo->id,
            'user_id' => $operator->id,
            'assigned_quantity' => 50,
            'status' => ProductionAssignment::STATUS_ASSIGNED,
        ]);
    }

    public function test_the_assignment_form_lists_only_operators(): void
    {
        $this->actingAs($this->ppic());

        $operators = Livewire::test(AssignmentManager::class)->instance()->operators();
        $numbers = $operators->pluck('employee_number')->all();

        $this->assertContains('PHM-EMP-001', $numbers);
        $this->assertNotContains('PHM-EMP-007', $numbers, 'Management is not an operator.');
        $this->assertNotContains('PHM-EMP-006', $numbers, 'A driver is not an operator.');
    }

    public function test_production_stages_are_configurable_data_not_hardcoded(): void
    {
        $this->assertDatabaseHas('production_stages', ['code' => 'LAS', 'is_active' => true]);

        ProductionStage::where('code', 'LAS')->update(['is_active' => false]);

        $this->actingAs($this->ppic());
        $stages = Livewire::test(AssignmentManager::class)->instance()->stages();

        $this->assertFalse(
            $stages->contains('code', 'LAS'),
            'Disabling a stage must remove it from the planner without a code change.',
        );
    }

    public function test_an_operator_cannot_create_assignments(): void
    {
        $this->actingAs($this->operator())
            ->get(route('planning.assignments'))
            ->assertForbidden();
    }

    public function test_a_supervisor_cannot_create_work_orders(): void
    {
        $this->actingAs($this->supervisor())
            ->get(route('planning.work-orders'))
            ->assertForbidden();
    }

    public function test_the_planning_service_refuses_an_unauthorized_caller(): void
    {
        $operator = $this->userWithRole(Roles::OPERATOR);

        $this->expectException(HttpException::class);

        app(PlanningService::class)->createWorkOrder($operator, [
            'customer_order_id' => null,
            'product_id' => Product::value('id'),
            'target_quantity' => 10,
            'planned_date' => today()->toDateString(),
            'notes' => null,
        ]);
    }
}
