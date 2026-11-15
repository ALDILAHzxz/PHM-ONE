<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\Shipment;
use App\Services\OperationsMetrics;
use App\Support\ShipmentStatus;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function index(OperationsMetrics $metrics): View
    {
        return view('shipments.index', [
            'summary' => $metrics->shippingSummary(),
            'active' => Shipment::query()
                ->with(['order.partner', 'order.product', 'driver'])
                ->inProgress()
                ->orderByRaw("CASE status
                    WHEN '".ShipmentStatus::ISSUE."' THEN 0
                    WHEN '".ShipmentStatus::PREPARED."' THEN 1
                    WHEN '".ShipmentStatus::IN_TRANSIT."' THEN 2
                    ELSE 3 END")
                ->orderBy('created_at')
                ->get(),
            'readyOrders' => CustomerOrder::active()
                ->with(['partner', 'product'])
                ->orderBy('delivery_target_date')
                ->get()
                ->filter(fn (CustomerOrder $o) => $o->readyToShipQuantity() > 0)
                ->values(),
        ]);
    }

    public function create(): View
    {
        return view('shipments.create');
    }

    public function history(): View
    {
        return view('shipments.history', [
            'shipments' => Shipment::query()
                ->with(['order.partner', 'order.product', 'driver', 'recorder'])
                ->whereIn('status', [ShipmentStatus::RECEIVED, ShipmentStatus::CANCELLED])
                ->latest('delivered_at')
                ->paginate(15),
        ]);
    }
}
