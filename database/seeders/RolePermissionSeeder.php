<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions as P;
use App\Support\Roles;
use Illuminate\Database\Seeder;

/**
 * Roles, permissions and the role -> permission matrix.
 * Mirrored in docs/ROLE_PERMISSION_MATRIX.md.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (P::ALL as $code => [$name, $group]) {
            Permission::updateOrCreate(['code' => $code], ['name' => $name, 'group' => $group]);
        }

        $roles = [
            Roles::OPERATOR => [
                'name' => 'Operator Produksi',
                'description' => 'Mencatat hasil pekerjaan harian.',
                'default_route' => 'operator.tasks',
                'sort_order' => 10,
                'permissions' => [
                    P::PRODUCTION_REPORT_CREATE,
                    P::PRODUCTION_REPORT_VIEW_OWN,
                    P::BEESHA_USE,
                ],
            ],
            Roles::SUPERVISOR => [
                'name' => 'Supervisor Produksi / QC',
                'description' => 'Memeriksa dan memverifikasi laporan produksi tim.',
                'default_route' => 'supervisor.reviews',
                'sort_order' => 20,
                'permissions' => [
                    P::PRODUCTION_REPORT_VIEW_OWN,
                    P::PRODUCTION_REPORT_VIEW_TEAM,
                    P::PRODUCTION_REPORT_REVIEW,
                    P::PLANNING_VIEW,
                    P::BEESHA_USE,
                ],
            ],
            Roles::ADMIN_ORDER => [
                'name' => 'Admin Pesanan',
                'description' => 'Mencatat dan memantau pesanan mitra.',
                'default_route' => 'orders.index',
                'sort_order' => 30,
                'permissions' => [
                    P::ORDER_VIEW,
                    P::ORDER_CREATE,
                    P::ORDER_UPDATE,
                    P::BEESHA_USE,
                ],
            ],
            Roles::PPIC => [
                'name' => 'PPIC',
                'description' => 'Merencanakan produksi dan menugaskan karyawan.',
                'default_route' => 'planning.index',
                'sort_order' => 40,
                'permissions' => [
                    P::ORDER_VIEW,
                    P::PLANNING_VIEW,
                    P::WORK_ORDER_MANAGE,
                    P::ASSIGNMENT_MANAGE,
                    P::PRODUCTION_REPORT_VIEW_TEAM,
                    P::SHIPMENT_VIEW,
                    P::BEESHA_USE,
                ],
            ],
            Roles::WAREHOUSE => [
                'name' => 'Petugas Gudang / Pengiriman',
                'description' => 'Mencatat pengiriman barang ke mitra.',
                'default_route' => 'shipments.index',
                'sort_order' => 50,
                'permissions' => [
                    P::ORDER_VIEW,
                    P::SHIPMENT_VIEW,
                    P::SHIPMENT_CREATE,
                    P::SHIPMENT_UPDATE_STATUS,
                    P::BEESHA_USE,
                ],
            ],
            Roles::DRIVER => [
                'name' => 'Driver',
                'description' => 'Mengantar barang dan memperbarui status perjalanan.',
                'default_route' => 'driver.shipments',
                'sort_order' => 60,
                'permissions' => [
                    P::SHIPMENT_VIEW_OWN,
                    P::SHIPMENT_UPDATE_STATUS,
                    P::BEESHA_USE,
                ],
            ],
            Roles::MANAGEMENT => [
                'name' => 'Pimpinan / Management',
                'description' => 'Memantau ringkasan operasional perusahaan.',
                'default_route' => 'dashboard',
                'sort_order' => 70,
                'permissions' => [
                    P::DASHBOARD_VIEW,
                    P::KPI_VIEW,
                    P::ORDER_VIEW,
                    P::PLANNING_VIEW,
                    P::SHIPMENT_VIEW,
                    P::PRODUCTION_REPORT_VIEW_TEAM,
                    P::AUDIT_VIEW,
                    P::BEESHA_USE,
                ],
            ],
            Roles::SYSADMIN => [
                'name' => 'Administrator Sistem',
                'description' => 'Mengelola akun, peran, dan pengaturan sistem.',
                'default_route' => 'system.users',
                'sort_order' => 80,
                'permissions' => [
                    P::USER_MANAGE,
                    P::ROLE_MANAGE,
                    P::AUDIT_VIEW,
                    P::SYSTEM_CONFIG,
                    P::BEESHA_USE,
                ],
            ],
        ];

        foreach ($roles as $code => $definition) {
            $role = Role::updateOrCreate(['code' => $code], [
                'name' => $definition['name'],
                'description' => $definition['description'],
                'default_route' => $definition['default_route'],
                'sort_order' => $definition['sort_order'],
            ]);

            $permissionIds = Permission::whereIn('code', $definition['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
