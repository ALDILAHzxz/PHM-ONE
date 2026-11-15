<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Support\ShipmentStatus;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function shipments(): View
    {
        return view('driver.shipments');
    }

    public function history(): View
    {
        return view('driver.history', [
            'shipments' => Shipment::query()
                ->with(['order.partner', 'order.product', 'events'])
                ->forDriver(request()->user()->id)   // scoped to the signed-in driver
                ->whereIn('status', [ShipmentStatus::RECEIVED, ShipmentStatus::CANCELLED])
                ->latest('delivered_at')
                ->paginate(12),
        ]);
    }
}
