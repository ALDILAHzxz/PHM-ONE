<?php

namespace App\Support;

use App\Models\User;

/**
 * Role-aware drawer navigation.
 *
 * The drawer shows only the routes that belong to the workspace the user is
 * currently acting in, so an operator never sees management menus. This is a
 * presentation filter — every route is independently guarded by the `role` and
 * `permission` middleware on the server.
 */
final class Navigation
{
    /**
     * @return array<int, array{label: string, route: string, icon: string, group?: string}>
     */
    public static function for(User $user): array
    {
        $role = $user->activeRole()?->code;

        $items = match ($role) {
            Roles::OPERATOR => [
                ['label' => 'Tugas Saya', 'route' => 'operator.tasks', 'icon' => 'clipboard-list'],
                ['label' => 'Catat Hasil', 'route' => 'operator.report.create', 'icon' => 'square-pen'],
                ['label' => 'Riwayat Saya', 'route' => 'operator.history', 'icon' => 'history'],
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
            Roles::SUPERVISOR => [
                ['label' => 'Pemeriksaan', 'route' => 'supervisor.reviews', 'icon' => 'list-checks'],
                ['label' => 'Kendala Produksi', 'route' => 'supervisor.issues', 'icon' => 'triangle-alert'],
                ['label' => 'Riwayat Tim', 'route' => 'supervisor.history', 'icon' => 'history'],
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
            Roles::ADMIN_ORDER => [
                ['label' => 'Pesanan Mitra', 'route' => 'orders.index', 'icon' => 'package'],
                ['label' => 'Tambah Pesanan', 'route' => 'orders.create', 'icon' => 'plus'],
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
            Roles::PPIC => [
                ['label' => 'Rencana Produksi', 'route' => 'planning.index', 'icon' => 'calendar-days'],
                ['label' => 'Work Order', 'route' => 'planning.work-orders', 'icon' => 'factory'],
                ['label' => 'Penugasan', 'route' => 'planning.assignments', 'icon' => 'users'],
                ['label' => 'Pesanan Mitra', 'route' => 'orders.index', 'icon' => 'package'],
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
            Roles::WAREHOUSE => [
                ['label' => 'Pengiriman Hari Ini', 'route' => 'shipments.index', 'icon' => 'truck'],
                ['label' => 'Catat Pengiriman', 'route' => 'shipments.create', 'icon' => 'square-pen'],
                ['label' => 'Riwayat Pengiriman', 'route' => 'shipments.history', 'icon' => 'history'],
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
            Roles::DRIVER => [
                ['label' => 'Pengiriman Saya', 'route' => 'driver.shipments', 'icon' => 'truck'],
                ['label' => 'Riwayat Perjalanan', 'route' => 'driver.history', 'icon' => 'history'],
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
            Roles::MANAGEMENT => [
                ['label' => 'Ringkasan', 'route' => 'dashboard', 'icon' => 'layout-dashboard'],
                ['label' => 'Produksi', 'route' => 'dashboard.production', 'icon' => 'factory'],
                ['label' => 'Pengiriman', 'route' => 'dashboard.shipping', 'icon' => 'truck'],
                ['label' => 'Peringatan', 'route' => 'dashboard.alerts', 'icon' => 'triangle-alert'],
                ['label' => 'Pesanan Mitra', 'route' => 'orders.index', 'icon' => 'package'],
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
            Roles::SYSADMIN => [
                ['label' => 'Kelola Akun', 'route' => 'system.users', 'icon' => 'users'],
                ['label' => 'Kelola Peran', 'route' => 'system.roles', 'icon' => 'shield-check'],
                ['label' => 'Audit Aktivitas', 'route' => 'system.audit', 'icon' => 'activity'],
                ['label' => 'Status Sistem', 'route' => 'system.status', 'icon' => 'database'],
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
            default => [
                ['label' => 'Bantuan', 'route' => 'help', 'icon' => 'circle-help'],
            ],
        };

        // Drop anything the user cannot actually reach, so the drawer never
        // offers a link that would return 403.
        return array_values(array_filter(
            $items,
            fn (array $item) => self::isReachable($user, $item['route'])
        ));
    }

    private static function isReachable(User $user, string $route): bool
    {
        $required = match ($route) {
            'operator.tasks', 'operator.report.create' => Permissions::PRODUCTION_REPORT_CREATE,
            'operator.history' => Permissions::PRODUCTION_REPORT_VIEW_OWN,
            'supervisor.reviews', 'supervisor.issues', 'supervisor.history' => Permissions::PRODUCTION_REPORT_REVIEW,
            'orders.index' => Permissions::ORDER_VIEW,
            'orders.create' => Permissions::ORDER_CREATE,
            'planning.index' => Permissions::PLANNING_VIEW,
            'planning.work-orders' => Permissions::WORK_ORDER_MANAGE,
            'planning.assignments' => Permissions::ASSIGNMENT_MANAGE,
            'shipments.index', 'shipments.history' => Permissions::SHIPMENT_VIEW,
            'shipments.create' => Permissions::SHIPMENT_CREATE,
            'driver.shipments', 'driver.history' => Permissions::SHIPMENT_VIEW_OWN,
            'dashboard', 'dashboard.production', 'dashboard.shipping', 'dashboard.alerts' => Permissions::DASHBOARD_VIEW,
            'system.users' => Permissions::USER_MANAGE,
            'system.roles' => Permissions::ROLE_MANAGE,
            'system.audit' => Permissions::AUDIT_VIEW,
            'system.status' => Permissions::SYSTEM_CONFIG,
            default => null,
        };

        return $required === null || $user->hasPermission($required);
    }
}
