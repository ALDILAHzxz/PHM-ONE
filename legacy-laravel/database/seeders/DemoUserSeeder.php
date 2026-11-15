<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Illuminate\Database\Seeder;

/**
 * Fictional demo identities.
 *
 * Every account here is marked is_demo = true. The demo login panel can only
 * ever sign in accounts carrying that flag, so it stays isolated from any real
 * identity that a future enterprise integration might create.
 *
 * The shared demo password is deliberately non-secret and documented in the
 * README; it is only usable while PHM_DEMO_MODE=true.
 */
class DemoUserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'demo1234';

    public function run(): void
    {
        $dept = Department::pluck('id', 'code');
        $shift = WorkShift::pluck('id', 'code');
        $roles = Role::pluck('id', 'code');

        $users = [
            // --- one demo account per role (shown on the demo login panel) ---
            [
                'employee_number' => 'PHM-EMP-001',
                'full_name' => 'Budi Santoso',
                'position' => 'Operator Produksi',
                'department' => 'PROD',
                'shift' => 'S1',
                'location' => 'Plant 1 — Lini Pengelasan',
                'email' => 'operator.demo@phm.example',
                'phone' => '0811-0000-001',
                'roles' => [Roles::OPERATOR],
            ],
            [
                'employee_number' => 'PHM-EMP-002',
                'full_name' => 'Siti Nurhaliza',
                'position' => 'Supervisor Produksi',
                'department' => 'QC',
                'shift' => 'S1',
                'location' => 'Plant 1 — Ruang QC',
                'email' => 'supervisor.demo@phm.example',
                'phone' => '0811-0000-002',
                'roles' => [Roles::SUPERVISOR],
            ],
            [
                'employee_number' => 'PHM-EMP-003',
                'full_name' => 'Dewi Kartika',
                'position' => 'Admin Pesanan',
                'department' => 'ADM',
                'shift' => 'S1',
                'location' => 'Kantor Pusat — Lantai 2',
                'email' => 'admin.demo@phm.example',
                'phone' => '0811-0000-003',
                'roles' => [Roles::ADMIN_ORDER],
            ],
            [
                'employee_number' => 'PHM-EMP-004',
                'full_name' => 'Agus Wijaya',
                'position' => 'Staff PPIC',
                'department' => 'PPIC',
                'shift' => 'S1',
                'location' => 'Plant 1 — Ruang Perencanaan',
                'email' => 'ppic.demo@phm.example',
                'phone' => '0811-0000-004',
                'roles' => [Roles::PPIC],
            ],
            [
                'employee_number' => 'PHM-EMP-005',
                'full_name' => 'Rudi Hartono',
                'position' => 'Petugas Gudang',
                'department' => 'GUDANG',
                'shift' => 'S1',
                'location' => 'Gudang Barang Jadi',
                'email' => 'gudang.demo@phm.example',
                'phone' => '0811-0000-005',
                'roles' => [Roles::WAREHOUSE],
            ],
            [
                'employee_number' => 'PHM-EMP-006',
                'full_name' => 'Joko Prasetyo',
                'position' => 'Driver Pengiriman',
                'department' => 'LOG',
                'shift' => 'S1',
                'location' => 'Pool Kendaraan',
                'email' => 'driver.demo@phm.example',
                'phone' => '0811-0000-006',
                'roles' => [Roles::DRIVER],
            ],
            [
                'employee_number' => 'PHM-EMP-007',
                'full_name' => 'Hartono Putra',
                'position' => 'Manajer Operasional',
                'department' => 'MGMT',
                'shift' => null,
                'location' => 'Kantor Pusat — Lantai 3',
                'email' => 'pimpinan.demo@phm.example',
                'phone' => '0811-0000-007',
                'roles' => [Roles::MANAGEMENT],
            ],
            [
                'employee_number' => 'PHM-EMP-008',
                'full_name' => 'Rizki Permana',
                'position' => 'Administrator Sistem',
                'department' => 'IT',
                'shift' => null,
                'location' => 'Kantor Pusat — Ruang Server',
                'email' => 'sysadmin.demo@phm.example',
                'phone' => '0811-0000-008',
                'roles' => [Roles::SYSADMIN],
            ],

            // --- multi-role example (workspace switcher in the profile menu) ---
            [
                'employee_number' => 'PHM-EMP-009',
                'full_name' => 'Yuni Astuti',
                'position' => 'Supervisor QC & PPIC',
                'department' => 'QC',
                'shift' => 'S2',
                'location' => 'Plant 1 — Ruang QC',
                'email' => 'multirole.demo@phm.example',
                'phone' => '0811-0000-009',
                'roles' => [Roles::SUPERVISOR, Roles::PPIC],
            ],

            // --- extra operators so team views have real content ---
            [
                'employee_number' => 'PHM-EMP-010',
                'full_name' => 'Ahmad Fauzi',
                'position' => 'Operator Produksi',
                'department' => 'PROD',
                'shift' => 'S1',
                'location' => 'Plant 1 — Lini Pemotongan',
                'email' => null,
                'phone' => '0811-0000-010',
                'roles' => [Roles::OPERATOR],
            ],
            [
                'employee_number' => 'PHM-EMP-011',
                'full_name' => 'Tri Wahyuni',
                'position' => 'Operator Produksi',
                'department' => 'PROD',
                'shift' => 'S1',
                'location' => 'Plant 1 — Lini Pengecatan',
                'email' => null,
                'phone' => '0811-0000-011',
                'roles' => [Roles::OPERATOR],
            ],
            [
                'employee_number' => 'PHM-EMP-012',
                'full_name' => 'Bambang Setiawan',
                'position' => 'Operator Produksi',
                'department' => 'PROD',
                'shift' => 'S2',
                'location' => 'Plant 1 — Lini Perakitan',
                'email' => null,
                'phone' => '0811-0000-012',
                'roles' => [Roles::OPERATOR],
            ],
            [
                'employee_number' => 'PHM-EMP-013',
                'full_name' => 'Eko Nugroho',
                'position' => 'Driver Pengiriman',
                'department' => 'LOG',
                'shift' => 'S2',
                'location' => 'Pool Kendaraan',
                'email' => null,
                'phone' => '0811-0000-013',
                'roles' => [Roles::DRIVER],
            ],
        ];

        foreach ($users as $row) {
            $user = User::updateOrCreate(
                ['employee_number' => $row['employee_number']],
                [
                    'full_name' => $row['full_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'password' => self::DEMO_PASSWORD,
                    'department_id' => $dept[$row['department']] ?? null,
                    'work_shift_id' => $row['shift'] !== null ? ($shift[$row['shift']] ?? null) : null,
                    'position' => $row['position'],
                    'work_location' => $row['location'],
                    'status' => 'active',
                    'is_demo' => true,
                ]
            );

            $pivot = [];
            foreach ($row['roles'] as $index => $roleCode) {
                if (isset($roles[$roleCode])) {
                    $pivot[$roles[$roleCode]] = ['is_primary' => $index === 0];
                }
            }

            $user->roles()->sync($pivot);
        }
    }
}
