<?php

namespace App\Support;

/**
 * Canonical permission codes. Every server-side authorization check resolves to
 * one of these; see docs/ROLE_PERMISSION_MATRIX.md for the role mapping.
 */
final class Permissions
{
    // Produksi — operator
    public const PRODUCTION_REPORT_CREATE = 'production.report.create';

    public const PRODUCTION_REPORT_VIEW_OWN = 'production.report.view_own';

    public const PRODUCTION_REPORT_EXCEPTION = 'production.report.exception';

    // Produksi — supervisor / QC
    public const PRODUCTION_REPORT_VIEW_TEAM = 'production.report.view_team';

    public const PRODUCTION_REPORT_REVIEW = 'production.report.review';

    // Pesanan mitra
    public const ORDER_VIEW = 'order.view';

    public const ORDER_CREATE = 'order.create';

    public const ORDER_UPDATE = 'order.update';

    // Perencanaan (PPIC)
    public const PLANNING_VIEW = 'planning.view';

    public const WORK_ORDER_MANAGE = 'work_order.manage';

    public const ASSIGNMENT_MANAGE = 'assignment.manage';

    // Pengiriman
    public const SHIPMENT_VIEW = 'shipment.view';

    public const SHIPMENT_CREATE = 'shipment.create';

    public const SHIPMENT_VIEW_OWN = 'shipment.view_own';

    public const SHIPMENT_UPDATE_STATUS = 'shipment.update_status';

    // Manajemen
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const KPI_VIEW = 'kpi.view';

    // Sistem
    public const USER_MANAGE = 'user.manage';

    public const ROLE_MANAGE = 'role.manage';

    public const AUDIT_VIEW = 'audit.view';

    public const SYSTEM_CONFIG = 'system.config';

    // Beesha AI
    public const BEESHA_USE = 'beesha.use';

    public const ALL = [
        self::PRODUCTION_REPORT_CREATE => ['Mencatat hasil produksi', 'produksi'],
        self::PRODUCTION_REPORT_VIEW_OWN => ['Melihat laporan sendiri', 'produksi'],
        self::PRODUCTION_REPORT_EXCEPTION => ['Mencatat melebihi sisa target (pengecualian)', 'produksi'],
        self::PRODUCTION_REPORT_VIEW_TEAM => ['Melihat laporan tim', 'produksi'],
        self::PRODUCTION_REPORT_REVIEW => ['Memeriksa dan memverifikasi laporan', 'produksi'],
        self::ORDER_VIEW => ['Melihat pesanan mitra', 'pesanan'],
        self::ORDER_CREATE => ['Menambah pesanan mitra', 'pesanan'],
        self::ORDER_UPDATE => ['Mengubah pesanan mitra', 'pesanan'],
        self::PLANNING_VIEW => ['Melihat rencana produksi', 'perencanaan'],
        self::WORK_ORDER_MANAGE => ['Mengelola work order', 'perencanaan'],
        self::ASSIGNMENT_MANAGE => ['Mengelola penugasan karyawan', 'perencanaan'],
        self::SHIPMENT_VIEW => ['Melihat pengiriman', 'pengiriman'],
        self::SHIPMENT_CREATE => ['Mencatat pengiriman', 'pengiriman'],
        self::SHIPMENT_VIEW_OWN => ['Melihat pengiriman sendiri', 'pengiriman'],
        self::SHIPMENT_UPDATE_STATUS => ['Memperbarui status pengiriman', 'pengiriman'],
        self::DASHBOARD_VIEW => ['Melihat ringkasan perusahaan', 'manajemen'],
        self::KPI_VIEW => ['Melihat KPI operasional', 'manajemen'],
        self::USER_MANAGE => ['Mengelola akun', 'sistem'],
        self::ROLE_MANAGE => ['Mengelola peran', 'sistem'],
        self::AUDIT_VIEW => ['Melihat audit aktivitas', 'sistem'],
        self::SYSTEM_CONFIG => ['Mengubah pengaturan sistem', 'sistem'],
        self::BEESHA_USE => ['Menggunakan Beesha AI', 'ai'],
    ];
}
