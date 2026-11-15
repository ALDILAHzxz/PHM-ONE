<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\ProductionAssignment;
use App\Models\WorkOrder;
use App\Services\OperationsMetrics;
use Illuminate\View\View;

class PlanningController extends Controller
{
    public function index(OperationsMetrics $metrics): View
    {
        return view('planning.index', [
            'production' => $metrics->productionForDate(),
            'ordersNeedingPlanning' => CustomerOrder::active()
                ->with(['partner', 'product'])
                ->orderBy('delivery_target_date')
                ->get()
                ->filter(function (CustomerOrder $order) {
                    $planned = (int) $order->workOrders()->sum('target_quantity');

                    return $planned < $order->quantity;
                })
                ->values(),
            'openWorkOrders' => WorkOrder::open()
                ->with(['product', 'customerOrder.partner'])
                ->orderBy('planned_date')
                ->get(),
            'todayAssignments' => ProductionAssignment::query()
                ->with(['user', 'workOrder.product'])
                ->withProgress()
                ->whereDate('planned_date', today())
                ->get(),
        ]);
    }

    public function workOrders(): View
    {
        return view('planning.work-orders');
    }

    public function assignments(): View
    {
        return view('planning.assignments');
    }
}
