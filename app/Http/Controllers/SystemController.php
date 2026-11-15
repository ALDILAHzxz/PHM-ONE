<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\BeeshaMessage;
use App\Models\CustomerOrder;
use App\Models\ProductionReport;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Beesha\BeeshaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function users(Request $request): View
    {
        $filters = $request->validate(['cari' => ['nullable', 'string', 'max:100']]);
        $search = $filters['cari'] ?? null;

        return view('system.users', [
            'users' => User::query()
                ->with(['roles', 'department'])
                ->when($search, fn ($q, string $term) => $q->where(function ($inner) use ($term) {
                    $inner->where('full_name', 'like', "%{$term}%")
                        ->orWhere('employee_number', 'like', "%{$term}%");
                }))
                ->orderBy('employee_number')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function roles(): View
    {
        return view('system.roles', [
            'roles' => Role::with(['permissions', 'users'])->orderBy('sort_order')->get(),
        ]);
    }

    public function audit(Request $request): View
    {
        $filters = $request->validate(['aksi' => ['nullable', 'string', 'max:64']]);
        $action = $filters['aksi'] ?? null;

        return view('system.audit', [
            'events' => AuditEvent::query()
                ->with('actor')
                ->when($action, fn ($q) => $q->where('action', $action))
                ->latest()
                ->paginate(30)
                ->withQueryString(),
            'actions' => AuditEvent::query()
                ->select('action', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('action')
                ->orderByDesc('jumlah')
                ->get(),
            'action' => $action,
        ]);
    }

    public function status(BeeshaService $beesha): View
    {
        return view('system.status', [
            'counts' => [
                'Karyawan aktif' => User::where('status', 'active')->count(),
                'Peran' => Role::count(),
                'Pesanan mitra' => CustomerOrder::count(),
                'Laporan produksi' => ProductionReport::count(),
                'Pengiriman' => Shipment::count(),
                'Kejadian audit' => AuditEvent::count(),
                'Pesan Beesha' => BeeshaMessage::count(),
            ],
            'runtime' => [
                'Versi PHP' => PHP_VERSION,
                'Versi Laravel' => app()->version(),
                'Driver database' => config('database.default'),
                'Driver sesi' => config('session.driver'),
                'Zona waktu' => config('app.timezone'),
                'Bahasa' => config('app.locale'),
            ],
            'flags' => [
                'Mode demo' => config('phm.demo_mode'),
                'OIDC SSO aktif' => config('phm.oidc.enabled'),
            ],
            'beesha' => [
                'Penyedia aktif' => $beesha->providerName(),
                'Model dikonfigurasi' => config('phm.beesha.model'),
                'Kunci API tersedia' => filled(config('phm.beesha.api_key')),
            ],
            'realtime' => [
                'Mekanisme' => 'Polling terotorisasi (Livewire wire:poll)',
                'Interval' => config('phm.poll_seconds').' detik',
            ],
        ]);
    }
}
