<?php

namespace App\Support;

/**
 * Canonical role codes. Kept in one place so routes, middleware, seeders and
 * navigation never drift apart on a string literal.
 */
final class Roles
{
    public const OPERATOR = 'operator';

    public const SUPERVISOR = 'supervisor';

    public const ADMIN_ORDER = 'admin_order';

    public const PPIC = 'ppic';

    public const WAREHOUSE = 'warehouse';

    public const DRIVER = 'driver';

    public const MANAGEMENT = 'management';

    public const SYSADMIN = 'sysadmin';

    public const ALL = [
        self::OPERATOR,
        self::SUPERVISOR,
        self::ADMIN_ORDER,
        self::PPIC,
        self::WAREHOUSE,
        self::DRIVER,
        self::MANAGEMENT,
        self::SYSADMIN,
    ];
}
