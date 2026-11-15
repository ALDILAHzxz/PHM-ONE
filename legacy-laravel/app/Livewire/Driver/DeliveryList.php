<?php

namespace App\Livewire\Driver;

use App\Models\Shipment;
use App\Services\ShipmentService;
use App\Support\ShipmentStatus;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Pengiriman Saya — mobile-first delivery status interface.
 *
 * Only transitions the service considers valid for the current state are
 * offered, and the service re-validates them, so a stale screen cannot record
 * a contradictory status.
 */
class DeliveryList extends Component
{
    /** Shipment currently collecting extra input, plus which action. */
    public ?int $actionFor = null;

    public ?string $pendingStatus = null;

    public string $recipientName = '';

    public string $actionNotes = '';

    /** @return Collection<int, Shipment> */
    #[Computed]
    public function shipments(): Collection
    {
        return Shipment::query()
            ->with(['order.partner', 'order.product', 'events.actor'])
            ->forDriver(auth()->id())
            ->inProgress()
            ->orderBy('shipped_at')
            ->orderBy('created_at')
            ->get();
    }

    public function startAction(int $shipmentId, string $status): void
    {
        $shipment = $this->shipments->firstWhere('id', $shipmentId);

        abort_if($shipment === null, 403);

        if (! ShipmentStatus::canTransition($shipment->status, $status)) {
            session()->flash('error', 'Status pengiriman sudah berubah. Halaman dimuat ulang.');
            unset($this->shipments);

            return;
        }

        // "Barang Diterima" and "Laporkan Kendala" need one more field; the
        // other transitions are immediate.
        if (in_array($status, [ShipmentStatus::RECEIVED, ShipmentStatus::ISSUE], true)) {
            $this->actionFor = $shipmentId;
            $this->pendingStatus = $status;
            $this->recipientName = '';
            $this->actionNotes = '';
            $this->resetErrorBag();

            return;
        }

        $this->apply($shipmentId, $status);
    }

    public function cancelAction(): void
    {
        $this->actionFor = null;
        $this->pendingStatus = null;
        $this->recipientName = '';
        $this->actionNotes = '';
        $this->resetErrorBag();
    }

    public function confirmAction(): void
    {
        if ($this->pendingStatus === ShipmentStatus::RECEIVED) {
            $this->validate(
                ['recipientName' => ['required', 'string', 'min:2', 'max:120']],
                ['recipientName.required' => 'Tuliskan nama penerima barang.'],
            );
        }

        if ($this->pendingStatus === ShipmentStatus::ISSUE) {
            $this->validate(
                ['actionNotes' => ['required', 'string', 'min:5', 'max:1000']],
                ['actionNotes.required' => 'Jelaskan kendala yang terjadi.'],
            );
        }

        $this->apply($this->actionFor, $this->pendingStatus);
    }

    private function apply(int $shipmentId, string $status): void
    {
        $shipment = Shipment::findOrFail($shipmentId);

        try {
            app(ShipmentService::class)->updateStatus(
                auth()->user(),
                $shipment,
                $status,
                $this->actionNotes ?: null,
                $this->recipientName ?: null,
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            session()->flash('error', $e->validator->errors()->first());
            unset($this->shipments);

            return;
        }

        session()->flash('status', sprintf(
            'Status pengiriman %s diperbarui menjadi %s.',
            $shipment->shipment_number,
            ShipmentStatus::label($status),
        ));

        $this->cancelAction();
        unset($this->shipments);
    }

    public function render()
    {
        return view('livewire.driver.delivery-list');
    }
}
