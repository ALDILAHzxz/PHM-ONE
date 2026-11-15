<?php

namespace App\Livewire\Orders;

use App\Models\CustomerOrder;
use App\Models\Partner;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Tambah Pesanan — five fields, everything else auto-populated from the
 * partner and product catalogues so nothing known is retyped.
 */
class OrderForm extends Component
{
    public string $step = 'form';

    public ?int $partnerId = null;

    public string $poNumber = '';

    public ?int $productId = null;

    public string $quantity = '';

    public string $deliveryTargetDate = '';

    public string $notes = '';

    public ?int $savedOrderId = null;

    public function mount(): void
    {
        $this->deliveryTargetDate = today()->addDays(14)->toDateString();
    }

    /** @return Collection<int, Partner> */
    #[Computed]
    public function partners(): Collection
    {
        return Partner::where('is_active', true)->orderBy('name')->get();
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function selectedPartner(): ?Partner
    {
        return $this->partnerId ? $this->partners->firstWhere('id', $this->partnerId) : null;
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        return $this->productId ? $this->products->firstWhere('id', $this->productId) : null;
    }

    #[Computed]
    public function savedOrder(): ?CustomerOrder
    {
        return $this->savedOrderId
            ? CustomerOrder::with(['partner', 'product'])->find($this->savedOrderId)
            : null;
    }

    protected function rules(): array
    {
        return [
            'partnerId' => ['required', 'integer', 'exists:partners,id'],
            'poNumber' => ['required', 'string', 'max:64'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'deliveryTargetDate' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'partnerId.required' => 'Pilih nama mitra.',
            'poNumber.required' => 'Masukkan nomor PO dari mitra.',
            'productId.required' => 'Pilih nama barang.',
            'quantity.required' => 'Masukkan jumlah pesanan.',
            'quantity.integer' => 'Jumlah pesanan harus berupa angka utuh.',
            'quantity.min' => 'Jumlah pesanan harus lebih dari 0.',
            'deliveryTargetDate.required' => 'Pilih target pengiriman.',
            'deliveryTargetDate.after_or_equal' => 'Target pengiriman tidak boleh di masa lalu.',
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
            $order = app(OrderService::class)->create(auth()->user(), [
                'partner_id' => $this->partnerId,
                'po_number' => trim($this->poNumber),
                'product_id' => $this->productId,
                'quantity' => (int) $this->quantity,
                'delivery_target_date' => $this->deliveryTargetDate,
                'notes' => $this->notes ?: null,
            ]);
        } catch (ValidationException $e) {
            $this->step = 'form';
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->savedOrderId = $order->id;
        $this->step = 'success';
    }

    public function addAnother(): void
    {
        $this->reset(['partnerId', 'poNumber', 'productId', 'quantity', 'notes', 'savedOrderId']);
        $this->deliveryTargetDate = today()->addDays(14)->toDateString();
        $this->step = 'form';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.orders.order-form');
    }
}
