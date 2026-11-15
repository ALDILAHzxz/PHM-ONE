<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Livewire\Auth\LoginForm;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDemoData();
    }

    public function test_login_page_is_reachable_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Selamat Datang di PHM One')
            ->assertSee('Nomor Induk Karyawan');
    }

    public function test_guest_is_redirected_to_login_from_a_protected_route(): void
    {
        $this->get(route('operator.tasks'))->assertRedirect(route('login'));
    }

    public function test_step_one_identifies_an_existing_employee(): void
    {
        Livewire::test(LoginForm::class)
            ->set('employee_number', 'PHM-EMP-001')
            ->call('identify')
            ->assertHasNoErrors()
            ->assertSet('step', 'authenticate')
            ->assertSet('identifiedName', 'Budi Santoso');
    }

    public function test_step_one_rejects_an_unknown_employee_number(): void
    {
        Livewire::test(LoginForm::class)
            ->set('employee_number', 'PHM-EMP-999')
            ->call('identify')
            ->assertHasErrors('employee_number')
            ->assertSet('step', 'identify');
    }

    public function test_step_one_rejects_an_inactive_account(): void
    {
        $this->userWithRole(Roles::OPERATOR, [
            'employee_number' => 'PHM-TEST-OFF',
            'status' => 'inactive',
        ]);

        Livewire::test(LoginForm::class)
            ->set('employee_number', 'PHM-TEST-OFF')
            ->call('identify')
            ->assertHasErrors('employee_number');
    }

    public function test_correct_password_authenticates_and_redirects_to_the_role_home(): void
    {
        Livewire::test(LoginForm::class)
            ->set('employee_number', 'PHM-EMP-001')
            ->call('identify')
            ->set('password', 'demo1234')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('operator.tasks'));

        $this->assertAuthenticatedAs($this->operator());
    }

    public function test_wrong_password_is_rejected(): void
    {
        Livewire::test(LoginForm::class)
            ->set('employee_number', 'PHM-EMP-001')
            ->call('identify')
            ->set('password', 'salah-sekali')
            ->call('authenticate')
            ->assertHasErrors('password');

        $this->assertGuest();
    }

    /**
     * Section 6.5: each role lands on its own workspace, with no intermediate
     * dashboard-selection screen.
     */
    public function test_each_role_is_redirected_to_its_own_workspace(): void
    {
        $expected = [
            'PHM-EMP-001' => 'operator.tasks',
            'PHM-EMP-002' => 'supervisor.reviews',
            'PHM-EMP-003' => 'orders.index',
            'PHM-EMP-004' => 'planning.index',
            'PHM-EMP-005' => 'shipments.index',
            'PHM-EMP-006' => 'driver.shipments',
            'PHM-EMP-007' => 'dashboard',
            'PHM-EMP-008' => 'system.users',
        ];

        foreach ($expected as $employeeNumber => $route) {
            Livewire::test(LoginForm::class)
                ->call('loginAsDemo', $employeeNumber)
                ->assertRedirect(route($route));

            $this->assertAuthenticatedAs($this->demoUser($employeeNumber));
            auth()->logout();
        }
    }

    public function test_demo_login_only_works_for_demo_accounts(): void
    {
        $real = $this->userWithRole(Roles::OPERATOR, [
            'employee_number' => 'PHM-REAL-001',
            'is_demo' => false,
        ]);

        Livewire::test(LoginForm::class)
            ->call('loginAsDemo', $real->employee_number)
            ->assertHasErrors('employee_number');

        $this->assertGuest();
    }

    public function test_demo_login_is_refused_when_demo_mode_is_disabled(): void
    {
        config(['phm.demo_mode' => false]);

        Livewire::test(LoginForm::class)
            ->call('loginAsDemo', 'PHM-EMP-001')
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_session_persists_across_requests(): void
    {
        $this->actingAs($this->operator());

        $this->get(route('operator.tasks'))->assertOk();
        $this->get(route('operator.history'))->assertOk();

        $this->assertAuthenticated();
    }

    public function test_logout_ends_the_session_without_deleting_records(): void
    {
        $user = $this->operator();
        $reportsBefore = $user->productionReports()->count();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame($reportsBefore, $user->productionReports()->count());
    }

    /**
     * The guard reloads the user from the database on every real request, so
     * the middleware is exercised directly here rather than through actingAs(),
     * which keeps one in-memory user instance for the whole test.
     */
    public function test_middleware_signs_out_an_account_that_is_no_longer_active(): void
    {
        $user = $this->operator();
        $user->status = 'inactive';

        $request = Request::create(route('operator.tasks'));
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => $user);

        Auth::setUser($user);

        $response = (new EnsureAccountIsActive)->handle(
            $request,
            fn () => new Response('halaman operator'),
        );

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertGuest();
    }

    public function test_middleware_lets_an_active_account_through(): void
    {
        $user = $this->operator();

        $request = Request::create(route('operator.tasks'));
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureAccountIsActive)->handle(
            $request,
            fn () => new Response('halaman operator'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('halaman operator', $response->getContent());
    }

    public function test_last_login_timestamp_is_recorded(): void
    {
        $this->assertNull($this->operator()->last_login_at);

        Livewire::test(LoginForm::class)->call('loginAsDemo', 'PHM-EMP-001');

        $this->assertNotNull($this->operator()->fresh()->last_login_at);
    }

    public function test_multi_role_user_starts_in_the_primary_workspace(): void
    {
        $user = $this->demoUser('PHM-EMP-009');
        $this->assertCount(2, $user->roles, 'PHM-EMP-009 should hold two roles.');

        Livewire::test(LoginForm::class)
            ->set('employee_number', 'PHM-EMP-009')
            ->call('identify')
            ->set('password', 'demo1234')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('supervisor.reviews'));

        $this->assertSame(Roles::SUPERVISOR, session('phm.active_role'));
    }

    /** The quick-login panel only lists single-role demo identities. */
    public function test_demo_panel_lists_one_account_per_role(): void
    {
        Livewire::test(LoginForm::class)
            ->assertSee('Operator Produksi')
            ->assertSee('Supervisor Produksi / QC')
            ->assertDontSee('Supervisor QC & PPIC');
    }

    public function test_profile_is_reachable_and_shows_the_employee_number(): void
    {
        $this->actingAs($this->operator())
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('PHM-EMP-001')
            ->assertSee('Budi Santoso');
    }
}
