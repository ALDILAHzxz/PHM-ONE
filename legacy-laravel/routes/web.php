<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\SystemController;
use App\Support\Permissions as P;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;

/*
|------------------------------------------------------------------------------
| Guest
|------------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'show'])->name('login');
    Route::get('/bantuan-masuk', [LoginController::class, 'help'])->name('login.help');
});

Route::get('/', [LoginController::class, 'show']);

Route::post('/keluar', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|------------------------------------------------------------------------------
| Authenticated
|------------------------------------------------------------------------------
| Every route below is guarded twice: `auth` + `active` for the session, and a
| `role` or `permission` guard for the workspace. Authorization is never left
| to the UI alone.
*/

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/beranda', HomeController::class)->name('home');
    Route::get('/bantuan', HelpController::class)->name('help');

    // ------------------------------------------------------------------ profile
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profil/peran', [ProfileController::class, 'switchRole'])->name('profile.switch-role');
    Route::put('/profil/kata-sandi', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ----------------------------------------------------------------- operator
    Route::prefix('operator')->name('operator.')->group(function () {
        Route::get('/tugas', [OperatorController::class, 'tasks'])
            ->middleware('permission:'.P::PRODUCTION_REPORT_CREATE)
            ->name('tasks');

        Route::get('/catat-hasil/{assignment?}', [OperatorController::class, 'createReport'])
            ->middleware('permission:'.P::PRODUCTION_REPORT_CREATE)
            ->name('report.create');

        Route::get('/riwayat', [OperatorController::class, 'history'])
            ->middleware('permission:'.P::PRODUCTION_REPORT_VIEW_OWN)
            ->name('history');
    });

    // --------------------------------------------------------------- supervisor
    Route::prefix('supervisor')->name('supervisor.')
        ->middleware('permission:'.P::PRODUCTION_REPORT_REVIEW)
        ->group(function () {
            Route::get('/pemeriksaan', [SupervisorController::class, 'reviews'])->name('reviews');
            Route::get('/kendala', [SupervisorController::class, 'issues'])->name('issues');
            Route::get('/riwayat', [SupervisorController::class, 'history'])->name('history');
        });

    // ------------------------------------------------------------------- orders
    Route::prefix('pesanan')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])
            ->middleware('permission:'.P::ORDER_VIEW)
            ->name('index');

        Route::get('/tambah', [OrderController::class, 'create'])
            ->middleware('permission:'.P::ORDER_CREATE)
            ->name('create');

        Route::get('/{order}', [OrderController::class, 'show'])
            ->middleware('permission:'.P::ORDER_VIEW)
            ->name('show');
    });

    // ----------------------------------------------------------------- planning
    Route::prefix('perencanaan')->name('planning.')->group(function () {
        Route::get('/', [PlanningController::class, 'index'])
            ->middleware('permission:'.P::PLANNING_VIEW)
            ->name('index');

        Route::get('/work-order', [PlanningController::class, 'workOrders'])
            ->middleware('permission:'.P::WORK_ORDER_MANAGE)
            ->name('work-orders');

        Route::get('/penugasan', [PlanningController::class, 'assignments'])
            ->middleware('permission:'.P::ASSIGNMENT_MANAGE)
            ->name('assignments');
    });

    // ---------------------------------------------------------------- shipments
    Route::prefix('pengiriman')->name('shipments.')->group(function () {
        Route::get('/', [ShipmentController::class, 'index'])
            ->middleware('permission:'.P::SHIPMENT_VIEW)
            ->name('index');

        Route::get('/catat', [ShipmentController::class, 'create'])
            ->middleware('permission:'.P::SHIPMENT_CREATE)
            ->name('create');

        Route::get('/riwayat', [ShipmentController::class, 'history'])
            ->middleware('permission:'.P::SHIPMENT_VIEW)
            ->name('history');
    });

    // ------------------------------------------------------------------- driver
    Route::prefix('driver')->name('driver.')
        ->middleware(['role:'.Roles::DRIVER, 'permission:'.P::SHIPMENT_VIEW_OWN])
        ->group(function () {
            Route::get('/pengiriman', [DriverController::class, 'shipments'])->name('shipments');
            Route::get('/riwayat', [DriverController::class, 'history'])->name('history');
        });

    // --------------------------------------------------------------- management
    Route::prefix('dashboard')
        ->middleware('permission:'.P::DASHBOARD_VIEW)
        ->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/produksi', [DashboardController::class, 'production'])->name('dashboard.production');
            Route::get('/pengiriman', [DashboardController::class, 'shipping'])->name('dashboard.shipping');
            Route::get('/peringatan', [DashboardController::class, 'alerts'])->name('dashboard.alerts');
        });

    // ------------------------------------------------------------------- system
    Route::prefix('sistem')->name('system.')->group(function () {
        Route::get('/pengguna', [SystemController::class, 'users'])
            ->middleware('permission:'.P::USER_MANAGE)
            ->name('users');

        Route::get('/peran', [SystemController::class, 'roles'])
            ->middleware('permission:'.P::ROLE_MANAGE)
            ->name('roles');

        Route::get('/audit', [SystemController::class, 'audit'])
            ->middleware('permission:'.P::AUDIT_VIEW)
            ->name('audit');

        Route::get('/status', [SystemController::class, 'status'])
            ->middleware('permission:'.P::SYSTEM_CONFIG)
            ->name('status');
    });
});
