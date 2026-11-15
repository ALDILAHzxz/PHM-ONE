<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\ProductionReport;
use App\Models\Shipment;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Human-readable document numbers.
 *
 * The daily counter is read inside the caller's transaction and the column has
 * a UNIQUE index, so a collision under concurrency surfaces as a database error
 * rather than two records quietly sharing a number.
 */
class DocumentNumbering
{
    public function productionReport(?Carbon $date = null): string
    {
        return $this->nextFor(ProductionReport::query(), 'report_number', 'LP', $date);
    }

    public function customerOrder(?Carbon $date = null): string
    {
        return $this->nextFor(CustomerOrder::query(), 'order_number', 'PO', $date);
    }

    public function workOrder(?Carbon $date = null): string
    {
        return $this->nextFor(WorkOrder::query(), 'wo_number', 'WO', $date);
    }

    public function shipment(?Carbon $date = null): string
    {
        return $this->nextFor(Shipment::query(), 'shipment_number', 'SJ', $date);
    }

    public function deliveryNote(?Carbon $date = null): string
    {
        return $this->nextFor(Shipment::query(), 'delivery_note_number', 'SRT', $date);
    }

    private function nextFor(mixed $query, string $column, string $prefix, ?Carbon $date): string
    {
        $date ??= now();
        $stamp = $date->format('Ymd');
        $pattern = sprintf('%s-%s-', $prefix, $stamp);

        $used = $query->where($column, 'like', $pattern.'%')
            ->select(DB::raw("MAX($column) as max_number"))
            ->value('max_number');

        $sequence = 1;

        if (is_string($used) && preg_match('/-(\d+)$/', $used, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $pattern.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
