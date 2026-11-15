<?php

namespace App\Livewire\Operator;

use App\Exceptions\ProductionReportException;
use App\Models\ProductionAssignment;
use App\Models\ProductionReport;
use App\Services\ProductionReportService;
use App\Support\IssueCategory;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Catat Hasil Produksi — the core operator workflow.
 *
 * Three states in one component:
 *   form    -> the single-column Google-Forms-style entry screen
 *   confirm -> a plain summary the operator confirms before anything is saved
 *   success -> shown only AFTER the service has committed the report
 *
 * No quantity arithmetic happens here; the server service owns the rules.
 */
class ProductionReportForm extends Component
{
    public ?int $assignmentId = null;

    public string $step = 'form';

    // ------------------------------------------------------------ form fields
    public string $quantity = '';

    /** 'tidak' | 'ada' — kept as a string so the radio group round-trips. */
    public string $issueState = 'tidak';

    public string $issueCategory = '';

    public string $issueDescription = '';

    public string $additionalNotes = '';

    /** Regenerated only when a NEW entry starts, so a double-click is ignored. */
    public string $idempotencyKey = '';

    // ----------------------------------------------------------- result state
    public ?int $savedReportId = null;

    public function mount(?ProductionAssignment $assignment = null): void
    {
        $this->idempotencyKey = app(ProductionReportService::class)->newIdempotencyKey();

        $candidates = $this->availableAssignments;

        if ($assignment !== null && $assignment->exists) {
            // Guard against someone else's assignment id in the URL.
            abort_unless($candidates->contains('id', $assignment->id), 403);
            $this->assignmentId = $assignment->id;

            return;
        }

        // One open assignment is the common case — select it so the operator
        // sees the form straight away instead of a pointless chooser.
        if ($candidates->count() === 1) {
            $this->assignmentId = $candidates->first()->id;
        }
    }

    // -------------------------------------------------------------- data reads

    /** @return Collection<int, ProductionAssignment> */
    #[Computed]
    public function availableAssignments(): Collection
    {
        return ProductionAssignment::query()
            ->with(['workOrder.product', 'stage', 'workStation'])
            ->withProgress()
            ->forUser(auth()->id())
            ->open()
            ->orderBy('planned_date')
            ->get();
    }

    #[Computed]
    public function assignment(): ?ProductionAssignment
    {
        if ($this->assignmentId === null) {
            return null;
        }

        return ProductionAssignment::query()
            ->with(['workOrder.product', 'stage', 'workStation', 'workShift'])
            ->withProgress()
            ->find($this->assignmentId);
    }

    #[Computed]
    public function savedReport(): ?ProductionReport
    {
        return $this->savedReportId !== null
            ? ProductionReport::with('assignment.workOrder.product')->find($this->savedReportId)
            : null;
    }

    // ------------------------------------------------------------- validation

    protected function rules(): array
    {
        return [
            'assignmentId' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'issueState' => ['required', 'in:tidak,ada'],
            'issueCategory' => [
                $this->issueState === 'ada' ? 'required' : 'nullable',
                'in:'.implode(',', array_keys(IssueCategory::OPTIONS)),
            ],
            'issueDescription' => [
                $this->issueState === 'ada' ? 'required' : 'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
            'additionalNotes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'assignmentId.required' => 'Pilih pekerjaan yang ingin Anda catat.',

            'quantity.required' => 'Masukkan jumlah hasil pekerjaan.',
            'quantity.integer' => 'Masukkan jumlah dalam satuan unit utuh.',
            'quantity.min' => 'Jumlah harus lebih dari 0.',
            'quantity.max' => 'Jumlah terlalu besar. Periksa kembali angkanya.',

            'issueCategory.required' => 'Pilih jenis kendala.',
            'issueCategory.in' => 'Pilih jenis kendala.',

            'issueDescription.required' => 'Jelaskan kendala yang dialami.',
            'issueDescription.min' => 'Jelaskan kendala sedikit lebih rinci.',
            'issueDescription.max' => 'Penjelasan kendala terlalu panjang (maksimal 1.000 karakter).',

            'additionalNotes.max' => 'Keterangan tambahan terlalu panjang (maksimal 1.000 karakter).',
        ];
    }

    /** Clear the conditional fields when the operator says there is no issue. */
    public function updatedIssueState(string $value): void
    {
        if ($value === 'tidak') {
            $this->issueCategory = '';
            $this->issueDescription = '';
            $this->resetErrorBag(['issueCategory', 'issueDescription']);
        }
    }

    public function selectAssignment(int $id): void
    {
        abort_unless($this->availableAssignments->contains('id', $id), 403);

        $this->assignmentId = $id;
        $this->resetErrorBag();
    }

    // ------------------------------------------------------------------ steps

    /** Step 3 of the submission flow: show a summary before saving anything. */
    public function review(): void
    {
        $this->validate();

        $assignment = $this->assignment;

        if ($assignment === null || ! $assignment->acceptsReports()) {
            $this->addError('assignmentId', 'Ada perubahan pada penugasan ini. Silakan periksa kembali.');

            return;
        }

        $this->step = 'confirm';
    }

    public function backToForm(): void
    {
        $this->step = 'form';
    }

    /** Step 4: hand everything to the server service. */
    public function save(): void
    {
        $this->validate();

        $assignment = $this->assignment;

        if ($assignment === null) {
            $this->addError('assignmentId', 'Penugasan tidak ditemukan. Muat ulang halaman.');
            $this->step = 'form';

            return;
        }

        try {
            $report = app(ProductionReportService::class)->submit(
                auth()->user(),
                $assignment,
                [
                    'quantity' => (int) $this->quantity,
                    'has_issue' => $this->issueState === 'ada',
                    'issue_category' => $this->issueCategory ?: null,
                    'issue_description' => $this->issueDescription ?: null,
                    'additional_notes' => $this->additionalNotes ?: null,
                    'idempotency_key' => $this->idempotencyKey,
                ],
            );
        } catch (ProductionReportException $e) {
            // Business rejection: go back to the form with the operator's
            // values intact and the message on the right field.
            $this->step = 'form';
            $this->addError($this->mapField($e->field), $e->getMessage());

            return;
        }

        // Success is only displayed once the row exists.
        $this->savedReportId = $report->id;
        $this->step = 'success';
    }

    /** Reset for another entry on the same (or another) assignment. */
    public function recordAnother(): void
    {
        $this->quantity = '';
        $this->issueState = 'tidak';
        $this->issueCategory = '';
        $this->issueDescription = '';
        $this->additionalNotes = '';
        $this->savedReportId = null;
        $this->idempotencyKey = app(ProductionReportService::class)->newIdempotencyKey();
        $this->step = 'form';
        $this->resetErrorBag();
    }

    private function mapField(string $serviceField): string
    {
        return match ($serviceField) {
            'assignment_id' => 'assignmentId',
            'quantity_completed' => 'quantity',
            default => 'quantity',
        };
    }

    public function render()
    {
        return view('livewire.operator.production-report-form', [
            'assignment' => $this->assignment,
            'assignments' => $this->availableAssignments,
            'issueOptions' => IssueCategory::OPTIONS,
            'savedReport' => $this->savedReport,
        ]);
    }
}
