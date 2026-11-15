<?php

namespace App\Livewire\Planning;

use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Services\PlanningService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WorkOrderManager extends Component
{
    public bool $showForm = false;

    public ?int $customerOrderId = null;

    public ?int $productId = null;

    public string $targetQuantity = '';

    public string $plannedDate = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->plannedDate = today()->toDateString();
    }

    /** @return Collection<int, WorkOrder> */
    #[Computed]
    public function workOrders(): Collection
    {
        return WorkOrder::query()
            ->with(['product', 'customerOrder.partner', 'assignments.user'])
            ->orderByDesc('planned_date')
            ->limit(25)
            ->get();
    }

    /** @return Collection<int, CustomerOrder> */
    #[Computed]
    public function openOrders(): Collection
    {
        return CustomerOrder::active()
            ->with(['partner', 'product'])
            ->orderBy('delivery_target_date')
            ->get();
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::where('is_active', true)->orderBy('name')->get();
    }

    /** Picking an order fills the product automatically. */
    public function updatedCustomerOrderId(mixed $value): void
    {
        $order = $this->openOrders->firstWhere('id', (int) $value);

        if ($order !== null) {
            $this->productId = $order->product_id;

            $planned = (int) $order->workOrders()->sum('target_quantity');
            $remaining = max(0, $order->quantity - $planned);

            if ($remaining > 0) {
                $this->targetQuantity = (string) $remaining;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'customerOrderId' => ['nullable', 'integer', 'exists:customer_orders,id'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'targetQuantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'plannedDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'productId.required' => 'Pilih produk yang akan diproduksi.',
            'targetQuantity.required' => 'Masukkan target jumlah produksi.',
            'targetQuantity.min' => 'Target harus lebih dari 0.',
            'plannedDate.required' => 'Pilih tanggal rencana produksi.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        try {
            $workOrder = app(PlanningService::class)->createWorkOrder(auth()->user(), [
                'customer_order_id' => $this->customerOrderId,
                'product_id' => $this->productId,
                'target_quantity' => (int) $this->targetQuantity,
                'planned_date' => $this->plannedDate,
                'notes' => $this->notes ?: null,
            ]);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        session()->flash('status', 'Work order '.$workOrder->wo_number.' berhasil dibuat.');

        $this->reset(['customerOrderId', 'productId', 'targetQuantity', 'notes', 'showForm']);
        $this->plannedDate = today()->toDateString();
        unset($this->workOrders);
    }

    public function render()
    {
        return view('livewire.planning.work-order-manager');
    }
}
