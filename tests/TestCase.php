<?php

namespace Tests;

use App\Models\ProductionAssignment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoOperationsSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Seed the role matrix and reference data only — no operational records. */
    protected function seedFoundation(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CatalogSeeder::class);
    }

    /** Seed everything, including the section 37 demonstration baseline. */
    protected function seedDemoData(): void
    {
        $this->seedFoundation();
        $this->seed(DemoUserSeeder::class);
        $this->seed(DemoOperationsSeeder::class);
    }

    protected function demoUser(string $employeeNumber): User
    {
        return User::where('employee_number', $employeeNumber)->firstOrFail();
    }

    protected function operator(): User
    {
        return $this->demoUser('PHM-EMP-001');
    }

    protected function supervisor(): User
    {
        return $this->demoUser('PHM-EMP-002');
    }

    protected function orderAdmin(): User
    {
        return $this->demoUser('PHM-EMP-003');
    }

    protected function ppic(): User
    {
        return $this->demoUser('PHM-EMP-004');
    }

    protected function warehouse(): User
    {
        return $this->demoUser('PHM-EMP-005');
    }

    protected function driver(): User
    {
        return $this->demoUser('PHM-EMP-006');
    }

    protected function management(): User
    {
        return $this->demoUser('PHM-EMP-007');
    }

    protected function sysadmin(): User
    {
        return $this->demoUser('PHM-EMP-008');
    }

    /** The DEMO-WO-004 assignment used by the specification's test scenario. */
    protected function scenarioAssignment(): ProductionAssignment
    {
        return ProductionAssignment::query()
            ->withProgress()
            ->whereHas('workOrder', fn ($q) => $q->where('wo_number', 'DEMO-WO-004'))
            ->firstOrFail();
    }

    /** Create a bare user with the given role, for authorization tests. */
    protected function userWithRole(string $roleCode, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $role = Role::where('code', $roleCode)->firstOrFail();
        $user->roles()->attach($role, ['is_primary' => true]);

        return $user->fresh(['roles.permissions']);
    }
}
