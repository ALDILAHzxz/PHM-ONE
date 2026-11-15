<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_keys(OrderStatus::OPTIONS))],
        ]);

        $search = $filters['cari'] ?? null;
        $status = $filters['status'] ?? null;

        $orders = CustomerOrder::query()
            ->with(['partner', 'product'])
            ->when($search, function ($query, string $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', "%{$term}%")
                        ->orWhere('po_number', 'like', "%{$term}%")
                        ->orWhereHas('partner', fn ($p) => $p->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('delivery_target_date')
            ->paginate(12)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'statusOptions' => OrderStatus::OPTIONS,
        ]);
    }

    public function create(): View
    {
        return view('orders.create');
    }

    public function show(CustomerOrder $order): View
    {
        $order->load([
            'partner',
            'product',
            'creator',
            'workOrders.product',
            'shipments.driver',
        ]);

        return view('orders.show', ['order' => $order]);
    }
}
