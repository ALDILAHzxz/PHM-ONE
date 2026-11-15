<div class="space-y-5">
    <x-page-header title="Penugasan Karyawan"
                   subtitle="Bagi work order menjadi tugas harian per operator.">
        <x-slot:actions>
            <button type="button" wire:click="$toggle('showForm')" class="phm-btn phm-btn-primary">
                <x-lucide-plus class="size-5" aria-hidden="true" />
                Buat Penugasan
            </button>
        </x-slot:actions>
    </x-page-header>

    @if ($showForm)
        <section class="phm-card" aria-labelledby="judul-form-tugas">
            <h2 id="judul-form-tugas" class="phm-card-heading">Buat Penugasan Baru</h2>

            @if ($this->openWorkOrders->isEmpty())
                <p class="mt-3 text-base text-muted">
                    Tidak ada work order dengan jumlah yang belum ditugaskan.
                    Buat work order terlebih dahulu di halaman Work Order.
                </p>
            @else
                <form wire:submit="save" class="mt-4 space-y-5">
                    <div>
                        <label for="wo" class="phm-label">
                            Work Order <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                        </label>
                        <select id="wo" wire:model.live="workOrderId" class="phm-input" required>
                            <option value="">— Pilih work order —</option>
                            @foreach ($this->openWorkOrders as $wo)
                                <option value="{{ $wo->id }}">
                                    {{ $wo->wo_number }} · {{ $wo->product->name }} ·
                                    sisa {{ number_format($wo->unassignedQuantity(), 0, ',', '.') }} {{ $wo->product->unit }}
                                </option>
                            @endforeach
                        </select>
                        @error('workOrderId')
                            <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="operator" class="phm-label">
                            Karyawan <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                        </label>
                        <select id="operator" wire:model="operatorId" class="phm-input" required>
                            <option value="">— Pilih karyawan —</option>
                            @foreach ($this->operators as $operator)
                                <option value="{{ $operator->id }}">
                                    {{ $operator->full_name }} ({{ $operator->employee_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('operatorId')
                            <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                        @error('operatorId')
                        @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="qty" class="phm-label">
                                Jumlah Ditugaskan <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                            </label>
                            <input id="qty" type="number" inputmode="numeric" step="1" min="1"
                                   wire:model="assignedQuantity" class="phm-input" required>
                            @error('assignedQuantity')
                                <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                            @if ($this->selectedWorkOrder)
                                <p class="phm-helper mt-2">
                                    Sisa belum ditugaskan:
                                    {{ number_format($this->selectedWorkOrder->unassignedQuantity(), 0, ',', '.') }}
                                    {{ $this->selectedWorkOrder->product->unit }}
                                </p>
                            @endif
                        </div>

                        <div>
                            <label for="tanggal" class="phm-label">
                                Tanggal Pelaksanaan <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                            </label>
                            <input id="tanggal" type="date" wire:model="plannedDate" class="phm-input" required>
                            @error('plannedDate')
                                <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="shift" class="phm-label">Shift</label>
                            <select id="shift" wire:model="workShiftId" class="phm-input">
                                <option value="">— Shift karyawan —</option>
                                @foreach ($this->shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->describe() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="stage" class="phm-label">Tahap</label>
                            <select id="stage" wire:model="stageId" class="phm-input">
                                <option value="">— Pilih tahap —</option>
                                @foreach ($this->stages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="station" class="phm-label">Stasiun</label>
                            <select id="station" wire:model="workStationId" class="phm-input">
                                <option value="">— Pilih stasiun —</option>
                                @foreach ($this->stations as $station)
                                    <option value="{{ $station->id }}">{{ $station->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="catatan" class="phm-label">
                            Catatan <span class="ml-1 text-sm font-normal text-muted">(opsional)</span>
                        </label>
                        <textarea id="catatan" wire:model="notes" rows="2" maxlength="1000" class="phm-textarea"></textarea>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row-reverse">
                        <button type="submit" class="phm-btn phm-btn-primary flex-1" wire:loading.attr="disabled">
                            Simpan Penugasan
                        </button>
                        <button type="button" wire:click="$set('showForm', false)"
                                class="phm-btn phm-btn-secondary sm:w-32">
                            Batal
                        </button>
                    </div>
                </form>
            @endif
        </section>
    @endif

    @forelse ($this->assignments as $assignment)
        <article class="phm-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="phm-helper">
                        {{ $assignment->woNumber() }} ·
                        {{ $assignment->planned_date->translatedFormat('d M Y') }}
                    </p>
                    <h2 class="phm-card-heading mt-0.5">{{ $assignment->user->full_name }}</h2>
                    <p class="phm-helper mt-1">
                        {{ $assignment->productName() }}
                        @if ($assignment->stage) · {{ $assignment->stage->name }} @endif
                        @if ($assignment->workStation) · {{ $assignment->workStation->name }} @endif
                        @if ($assignment->workShift) · {{ $assignment->workShift->name }} @endif
                    </p>
                </div>
                <span class="phm-badge shrink-0 bg-brand/12 text-interactive ring-brand/30">
                    {{ $assignment->statusLabel() }}
                </span>
            </div>

            <x-progress-bar class="mt-4"
                            :reported="$assignment->reportedQty()"
                            :verified="$assignment->verifiedQty()"
                            :target="$assignment->targetQty()"
                            :unit="$assignment->unit()" />
        </article>
    @empty
        <x-empty-state icon="users"
                       title="Belum ada penugasan"
                       message="Buat penugasan agar operator melihat pekerjaannya di halaman Tugas Saya." />
    @endforelse
</div>
