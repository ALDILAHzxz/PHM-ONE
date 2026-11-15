<?php

namespace App\Livewire\Planning;

use App\Models\ProductionAssignment;
use App\Models\ProductionStage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkShift;
use App\Models\WorkStation;
use App\Services\PlanningService;
use App\Support\Permissions;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AssignmentManager extends Component
{
    public bool $showForm = false;

    public ?int $workOrderId = null;

    public ?int $operatorId = null;

    public string $assignedQuantity = '';

    public string $plannedDate = '';

    public ?int $workShiftId = null;

    public ?int $stageId = null;

    public ?int $workStationId = null;

    public string $notes = '';

    public function mount(): void
    {
        $this->plannedDate = today()->toDateString();
    }

    /** @return Collection<int, ProductionAssignment> */
    #[Computed]
    public function assignments(): Collection
    {
        return ProductionAssignment::query()
            ->with(['user', 'workOrder.product', 'stage', 'workStation', 'workShift'])
            ->withProgress()
            ->orderByDesc('planned_date')
            ->limit(25)
            ->get();
    }

    /** @return Collection<int, WorkOrder> */
    #[Computed]
    public function openWorkOrders(): Collection
    {
        return WorkOrder::query()
            ->open()
            ->with('product')
            ->orderBy('planned_date')
            ->get()
            ->filter(fn (WorkOrder $wo) => $wo->unassignedQuantity() > 0)
            ->values();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function operators(): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('roles.permissions', fn ($q) => $q->where('code', Permissions::PRODUCTION_REPORT_CREATE))
            ->orderBy('full_name')
            ->get();
    }

    #[Computed]
    public function shifts(): Collection
    {
        return WorkShift::where('is_active', true)->orderBy('code')->get();
    }

    #[Computed]
    public function stages(): Collection
    {
        return ProductionStage::where('is_active', true)->orderBy('sequence')->get();
    }

    #[Computed]
    public function stations(): Collection
    {
        return WorkStation::where('is_active', true)->orderBy('code')->get();
    }

    #[Computed]
    public function selectedWorkOrder(): ?WorkOrder
    {
        return $this->workOrderId ? $this->openWorkOrders->firstWhere('id', $this->workOrderId) : null;
    }

    public function updatedWorkOrderId(mixed $value): void
    {
        $wo = $this->openWorkOrders->firstWhere('id', (int) $value);

        if ($wo !== null) {
            $this->assignedQuantity = (string) $wo->unassignedQuantity();
            $this->plannedDate = $wo->planned_date->toDateString();
        }
    }

    protected function rules(): array
    {
        return [
            'workOrderId' => ['required', 'integer', 'exists:work_orders,id'],
            'operatorId' => ['required', 'integer', 'exists:users,id'],
            'assignedQuantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'plannedDate' => ['required', 'date'],
            'workShiftId' => ['nullable', 'integer', 'exists:work_shifts,id'],
            'stageId' => ['nullable', 'integer', 'exists:production_stages,id'],
            'workStationId' => ['nullable', 'integer', 'exists:work_stations,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'workOrderId.required' => 'Pilih work order yang akan ditugaskan.',
            'operatorId.required' => 'Pilih karyawan yang akan mengerjakan.',
            'assignedQuantity.required' => 'Masukkan jumlah yang ditugaskan.',
            'assignedQuantity.min' => 'Jumlah harus lebih dari 0.',
            'plannedDate.required' => 'Pilih tanggal pelaksanaan.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        try {
            app(PlanningService::class)->createAssignment(auth()->user(), [
                'work_order_id' => $this->workOrderId,
                'user_id' => $this->operatorId,
                'assigned_quantity' => (int) $this->assignedQuantity,
                'planned_date' => $this->plannedDate,
                'work_shift_id' => $this->workShiftId,
                'production_stage_id' => $this->stageId,
                'work_station_id' => $this->workStationId,
                'notes' => $this->notes ?: null,
            ]);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        session()->flash('status', 'Penugasan berhasil dibuat dan langsung terlihat oleh operator.');

        $this->reset(['workOrderId', 'operatorId', 'assignedQuantity', 'stageId', 'workStationId', 'notes', 'showForm']);
        $this->plannedDate = today()->toDateString();
        unset($this->assignments, $this->openWorkOrders);
    }

    public function render()
    {
        return view('livewire.planning.assignment-manager');
    }
}
