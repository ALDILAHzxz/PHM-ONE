<?php

namespace App\Http\Controllers;

use App\Models\ProductionReport;
use App\Support\ReportStatus;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    public function reviews(): View
    {
        return view('supervisor.reviews');
    }

    /** Kendala Produksi — operator-reported problems, newest first. */
    public function issues(): View
    {
        $issues = ProductionReport::query()
            ->with(['user', 'assignment.workOrder.product', 'assignment.stage'])
            ->withIssue()
            ->whereIn('status', [ReportStatus::PENDING, ReportStatus::VERIFIED])
            ->latest('submitted_at')
            ->paginate(15);

        return view('supervisor.issues', ['issues' => $issues]);
    }

    /** Riwayat Tim — every reviewed report, with who decided what. */
    public function history(): View
    {
        $reports = ProductionReport::query()
            ->with(['user', 'assignment.workOrder.product', 'latestReview.reviewer'])
            ->whereIn('status', [
                ReportStatus::VERIFIED,
                ReportStatus::NEEDS_CORRECTION,
                ReportStatus::PENDING,
            ])
            ->latest('submitted_at')
            ->paginate(15);

        return view('supervisor.history', ['reports' => $reports]);
    }
}
