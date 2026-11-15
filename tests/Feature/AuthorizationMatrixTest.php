<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\ProductionReport;
use App\Models\Shipment;
use App\Services\ShipmentService;
use App\Support\Navigation;
use App\Support\Roles;
use App\Support\ShipmentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Server-side authorization is the real boundary; the UI only hides what has
 * already been denied. This walks every workspace route against every role.
 */
class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    /** @return array<string, array{0: string, 1: array<int, string>}> */
    public static function routeMatrix(): array
    {
        return [
            // route name => roles that must be ALLOWED
            'operator.tasks' => ['operator.tasks', [Roles::OPERATOR]],
            // "Riwayat Saya" is scoped to the signed-in user, so anyone holding
            // view_own may open it and will only ever see their own reports.
            'operator.history' => ['operator.history', [Roles::OPERATOR, Roles::SUPERVISOR]],
            'supervisor.reviews' => ['supervisor.reviews', [Roles::SUPERVISOR]],
            'supervisor.issues' => ['supervisor.issues', [Roles::SUPERVISOR]],
            'supervisor.history' => ['supervisor.history', [Roles::SUPERVISOR]],
            'orders.index' => ['orders.index', [Roles::ADMIN_ORDER, Roles::PPIC, Roles::WAREHOUSE, Roles::MANAGEMENT]],
            'orders.create' => ['orders.create', [Roles::ADMIN_ORDER]],
            'planning.index' => ['planning.index', [Roles::PPIC, Roles::SUPERVISOR, Roles::MANAGEMENT]],
            'planning.work-orders' => ['planning.work-orders', [Roles::PPIC]],
            'planning.assignments' => ['planning.assignments', [Roles::PPIC]],
            'shipments.index' => ['shipments.index', [Roles::WAREHOUSE, Roles::PPIC, Roles::MANAGEMENT]],
            'shipments.create' => ['shipments.create', [Roles::WAREHOUSE]],
            'shipments.history' => ['shipments.history', [Roles::WAREHOUSE, Roles::PPIC, Roles::MANAGEMENT]],
            'driver.shipments' => ['driver.shipments', [Roles::DRIVER]],
            'driver.history' => ['driver.history', [Roles::DRIVER]],
            'dashboard' => ['dashboard', [Roles::MANAGEMENT]],
            'dashboard.production' => ['dashboard.production', [Roles::MANAGEMENT]],
            'dashboard.shipping' => ['dashboard.shipping', [Roles::MANAGEMENT]],
            'dashboard.alerts' => ['dashboard.alerts', [Roles::MANAGEMENT]],
            'system.users' => ['system.users', [Roles::SYSADMIN]],
            'system.roles' => ['system.roles', [Roles::SYSADMIN]],
            'system.audit' => ['system.audit', [Roles::SYSADMIN, Roles::MANAGEMENT]],
            'system.status' => ['system.status', [Roles::SYSADMIN]],
        ];
    }

    /**
     * @param  array<int, string>  $allowedRoles
     */
    #[DataProvider('routeMatrix')]
    public function test_route_is_reachable_only_by_authorized_roles(string $routeName, array $allowedRoles): void
    {
        $demoByRole = [
            Roles::OPERATOR => 'PHM-EMP-001',
            Roles::SUPERVISOR => 'PHM-EMP-002',
            Roles::ADMIN_ORDER => 'PHM-EMP-003',
            Roles::PPIC => 'PHM-EMP-004',
            Roles::WAREHOUSE => 'PHM-EMP-005',
            Roles::DRIVER => 'PHM-EMP-006',
            Roles::MANAGEMENT => 'PHM-EMP-007',
            Roles::SYSADMIN => 'PHM-EMP-008',
        ];

        foreach ($demoByRole as $role => $employeeNumber) {
            $user = $this->demoUser($employeeNumber);
            $expectAllowed = in_array($role, $allowedRoles, true);

            $response = $this->actingAs($user)->get(route($routeName));

            if ($expectAllowed) {
                $this->assertSame(
                    200,
                    $response->getStatusCode(),
                    sprintf('%s should be able to open %s.', $role, $routeName),
                );
            } else {
                $this->assertSame(
                    403,
                    $response->getStatusCode(),
                    sprintf('%s must NOT be able to open %s.', $role, $routeName),
                );
            }
        }
    }

    public function test_drawer_navigation_only_offers_reachable_routes(): void
    {
        foreach (['PHM-EMP-001', 'PHM-EMP-002', 'PHM-EMP-005', 'PHM-EMP-007'] as $employeeNumber) {
            $user = $this->demoUser($employeeNumber);
            $items = Navigation::for($user);

            $this->assertNotEmpty($items, $employeeNumber.' should have navigation entries.');

            foreach ($items as $item) {
                $response = $this->actingAs($user)->get(route($item['route']));

                $this->assertNotSame(
                    403,
                    $response->getStatusCode(),
                    sprintf(
                        'Drawer offered %s to %s but the server refuses it.',
                        $item['route'],
                        $employeeNumber,
                    ),
                );
            }
        }
    }

    public function test_an_operator_cannot_open_another_operators_report(): void
    {
        $otherReport = ProductionReport::whereNot('user_id', $this->operator()->id)->firstOrFail();

        $this->assertFalse(
            Gate::forUser($this->operator())
                ->allows('view-production-report', $otherReport),
        );
    }

    public function test_a_supervisor_may_view_team_reports(): void
    {
        $report = ProductionReport::whereNot('user_id', $this->supervisor()->id)->firstOrFail();

        $this->assertTrue(
            Gate::forUser($this->supervisor())
                ->allows('view-production-report', $report),
        );
    }

    public function test_a_driver_cannot_move_a_shipment_assigned_to_someone_else(): void
    {
        $otherDriver = $this->demoUser('PHM-EMP-013');

        $shipment = Shipment::where('driver_id', $otherDriver->id)
            ->where('status', ShipmentStatus::PREPARED)
            ->first()
            ?? Shipment::create([
                'shipment_number' => 'TEST-SJ-900',
                'customer_order_id' => CustomerOrder::value('id'),
                'quantity_shipped' => 10,
                'driver_id' => $otherDriver->id,
                'status' => ShipmentStatus::PREPARED,
                'recorded_by' => $this->warehouse()->id,
                'idempotency_key' => 'test-other-driver',
            ]);

        $this->expectException(ValidationException::class);

        app(ShipmentService::class)->updateStatus(
            $this->driver(),
            $shipment,
            ShipmentStatus::IN_TRANSIT,
        );
    }

    public function test_profile_role_switch_rejects_a_role_the_user_does_not_hold(): void
    {
        $this->actingAs($this->operator())
            ->post(route('profile.switch-role'), ['role' => Roles::MANAGEMENT])
            ->assertForbidden();

        $this->assertNotSame(Roles::MANAGEMENT, session('phm.active_role'));
    }

    public function test_profile_role_switch_works_for_a_role_the_user_does_hold(): void
    {
        $multiRole = $this->demoUser('PHM-EMP-009');

        $this->actingAs($multiRole)
            ->post(route('profile.switch-role'), ['role' => Roles::PPIC])
            ->assertRedirect(route('planning.index'));

        $this->assertSame(Roles::PPIC, session('phm.active_role'));
    }

    public function test_help_and_profile_are_available_to_every_role(): void
    {
        foreach (['PHM-EMP-001', 'PHM-EMP-004', 'PHM-EMP-006', 'PHM-EMP-008'] as $employeeNumber) {
            $user = $this->demoUser($employeeNumber);

            $this->actingAs($user)->get(route('help'))->assertOk();
            $this->actingAs($user)->get(route('profile'))->assertOk();
        }
    }
}
