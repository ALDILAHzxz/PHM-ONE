<?php

namespace App\Livewire\Shipping;

use App\Models\CustomerOrder;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use App\Support\Permissions;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Catat Pengiriman — mirrors the production form's shape: read-only order
 * context, then the single number the warehouse clerk actually types.
 */
class ShipmentForm extends Component
{
    public string $step = 'form';

    public ?int $orderId = null;

    public string $quantity = '';

    public string $deliveryNote = '';

    public ?int $driverId = null;

    public string $notes = '';

    public string $idempotencyKey = '';

    public ?int $savedShipmentId = null;

    public function mount(): void
    {
        $this->idempotencyKey = app(ShipmentService::class)->newIdempotencyKey();

        if ($this->shippableOrders->count() === 1) {
            $this->orderId = $this->shippableOrders->first()->id;
        }
    }

    /** Orders with verified stock not yet shipped. */
    #[Computed]
    public function shippableOrders(): Collection
    {
        return CustomerOrder::active()
            ->with(['partner', 'product'])
            ->orderBy('delivery_target_date')
            ->get()
            ->filter(fn (CustomerOrder $o) => $o->remainingToShipQuantity() > 0)
            ->values();
    }

    #[Computed]
    public function order(): ?CustomerOrder
    {
        return $this->orderId ? $this->shippableOrders->firstWhere('id', $this->orderId) : null;
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function drivers(): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('roles.permissions', fn ($q) => $q->where('code', Permissions::SHIPMENT_VIEW_OWN))
            ->orderBy('full_name')
            ->get();
    }

    #[Computed]
    public function savedShipment(): ?Shipment
    {
        return $this->savedShipmentId
            ? Shipment::with(['order.partner', 'order.product', 'driver'])->find($this->savedShipmentId)
            : null;
    }

    protected function rules(): array
    {
        return [
            'orderId' => ['required', 'integer', 'exists:customer_orders,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'deliveryNote' => ['nullable', 'string', 'max:64'],
            'driverId' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'orderId.required' => 'Pilih pesanan yang akan dikirim.',
            'quantity.required' => 'Masukkan jumlah barang yang dikirim.',
            'quantity.integer' => 'Masukkan jumlah dalam satuan unit utuh.',
            'quantity.min' => 'Jumlah harus lebih dari 0.',
        ];
    }

    public function review(): void
    {
        $this->validate();
        $this->step = 'confirm';
    }

    public function backToForm(): void
    {
        $this->step = 'form';
    }

    public function save(): void
    {
        $this->validate();

        try {
            $shipment = app(ShipmentService::class)->record(auth()->user(), [
                'customer_order_id' => $this->orderId,
                'quantity' => (int) $this->quantity,
                'delivery_note_number' => $this->deliveryNote ?: null,
                'driver_id' => $this->driverId,
                'notes' => $this->notes ?: null,
                'idempotency_key' => $this->idempotencyKey,
            ]);
        } catch (ValidationException $e) {
            $this->step = 'form';
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->savedShipmentId = $shipment->id;
        $this->step = 'success';
    }

    public function recordAnother(): void
    {
        $this->reset(['quantity', 'deliveryNote', 'driverId', 'notes', 'savedShipmentId']);
        $this->idempotencyKey = app(ShipmentService::class)->newIdempotencyKey();
        $this->step = 'form';
        $this->resetErrorBag();
        unset($this->shippableOrders);
    }

    public function render()
    {
        return view('livewire.shipping.shipment-form');
    }
}
