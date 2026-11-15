<?php

namespace App\Providers;

use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Models\Shipment;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Every permission code becomes a Gate ability, plus a handful of record-level
 * gates. All authorization in this application is evaluated on the server; the
 * UI only ever hides what a gate has already denied.
 */
class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 1. One gate per permission code.
        foreach (array_keys(Permissions::ALL) as $code) {
            Gate::define($code, fn (User $user) => $user->hasPermission($code));
        }

        // 2. Record-level gates.

        // An operator may only report against an assignment that is theirs and
        // still open. Supervisors/PPIC cannot submit on someone else's behalf.
        Gate::define('report-on-assignment', function (User $user, ProductionAssignment $assignment) {
            return $user->hasPermission(Permissions::PRODUCTION_REPORT_CREATE)
                && $assignment->user_id === $user->id
                && $assignment->acceptsReports();
        });

        // Viewing a single production report: the author, or anyone who may
        // review team reports.
        Gate::define('view-production-report', function (User $user, ProductionReport $report) {
            if ($report->user_id === $user->id && $user->hasPermission(Permissions::PRODUCTION_REPORT_VIEW_OWN)) {
                return true;
            }

            return $user->hasPermission(Permissions::PRODUCTION_REPORT_VIEW_TEAM);
        });

        // Reviewing requires the review permission, and nobody may verify
        // their own production report.
        Gate::define('review-production-report', function (User $user, ProductionReport $report) {
            return $user->hasPermission(Permissions::PRODUCTION_REPORT_REVIEW)
                && $report->user_id !== $user->id;
        });

        // A driver may only move their own shipment; warehouse staff may move
        // any shipment they are authorized to update.
        Gate::define('update-shipment-status', function (User $user, Shipment $shipment) {
            if ($user->hasRole(Roles::DRIVER) && $shipment->driver_id === $user->id) {
                return $user->hasPermission(Permissions::SHIPMENT_UPDATE_STATUS);
            }

            return $user->hasPermission(Permissions::SHIPMENT_UPDATE_STATUS)
                && $user->hasPermission(Permissions::SHIPMENT_VIEW);
        });
    }
}
