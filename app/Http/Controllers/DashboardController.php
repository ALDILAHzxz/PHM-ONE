<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\OperationsMetrics;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly OperationsMetrics $metrics) {}

    public function index(): View
    {
        return view('dashboard.index');
    }

    public function production(): View
    {
        return view('dashboard.production', [
            'today' => $this->metrics->productionForDate(),
            'yesterday' => $this->metrics->productionForDate(today()->subDay()),
            'progress' => $this->metrics->productionProgress(25),
        ]);
    }

    public function shipping(): View
    {
        return view('dashboard.shipping', [
            'summary' => $this->metrics->shippingSummary(),
            'shipments' => Shipment::query()
                ->with(['order.partner', 'order.product', 'driver'])
                ->latest('created_at')
                ->paginate(15),
        ]);
    }

    public function alerts(): View
    {
        return view('dashboard.alerts', [
            'alerts' => $this->metrics->alerts(),
        ]);
    }
}
