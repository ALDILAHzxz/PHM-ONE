<div class="space-y-5">
    <x-page-header title="Work Order"
                   subtitle="Rencana produksi yang diturunkan dari pesanan mitra.">
        <x-slot:actions>
            <button type="button" wire:click="$toggle('showForm')" class="phm-btn phm-btn-primary">
                <x-lucide-plus class="size-5" aria-hidden="true" />
                Buat Work Order
            </button>
        </x-slot:actions>
    </x-page-header>

    @if ($showForm)
        <section class="phm-card" aria-labelledby="judul-form-wo">
            <h2 id="judul-form-wo" class="phm-card-heading">Buat Work Order Baru</h2>

            <form wire:submit="save" class="mt-4 space-y-5">
                <div>
                    <label for="order" class="phm-label">
                        Pesanan Mitra <span class="ml-1 text-sm font-normal text-muted">(opsional)</span>
                    </label>
                    <select id="order" wire:model.live="customerOrderId" class="phm-input">
                        <option value="">— Produksi tanpa pesanan tertentu —</option>
                        @foreach ($this->openOrders as $order)
                            <option value="{{ $order->id }}">
                                {{ $order->order_number }} · {{ $order->partner->name }} ·
                                {{ number_format($order->quantity, 0, ',', '.') }} {{ $order->product->unit }}
                            </option>
                        @endforeach
                    </select>
                    <p class="phm-helper mt-2">Memilih pesanan akan mengisi produk dan sisa jumlah otomatis.</p>
                </div>

                <div>
                    <label for="product" class="phm-label">
                        Produk <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                    </label>
                    <select id="product" wire:model="productId" class="phm-input" required>
                        <option value="">— Pilih produk —</option>
                        @foreach ($this->products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    @error('productId')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="target" class="phm-label">
                            Target Produksi <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                        </label>
                        <input id="target" type="number" inputmode="numeric" step="1" min="1"
                               wire:model="targetQuantity" class="phm-input" placeholder="0" required>
                        @error('targetQuantity')
                            <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date" class="phm-label">
                            Tanggal Rencana <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                        </label>
                        <input id="date" type="date" wire:model="plannedDate" class="phm-input" required>
                        @error('plannedDate')
                            <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="phm-label">
                        Catatan <span class="ml-1 text-sm font-normal text-muted">(opsional)</span>
                    </label>
                    <textarea id="notes" wire:model="notes" rows="2" maxlength="1000" class="phm-textarea"></textarea>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row-reverse">
                    <button type="submit" class="phm-btn phm-btn-primary flex-1" wire:loading.attr="disabled">
                        Simpan Work Order
                    </button>
                    <button type="button" wire:click="$set('showForm', false)"
                            class="phm-btn phm-btn-secondary sm:w-32">
                        Batal
                    </button>
                </div>
            </form>
        </section>
    @endif

    @forelse ($this->workOrders as $wo)
        <article class="phm-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="phm-helper">{{ $wo->wo_number }}</p>
                    <h2 class="phm-card-heading mt-0.5">{{ $wo->product->name }}</h2>
                    <p class="phm-helper mt-1">
                        @if ($wo->customerOrder)
                            {{ $wo->customerOrder->order_number }} · {{ $wo->customerOrder->partner->name }}
                        @else
                            Tanpa pesanan terkait
                        @endif
                        · rencana {{ $wo->planned_date->translatedFormat('d M Y') }}
                    </p>
                </div>
                <span class="phm-badge shrink-0 bg-brand/12 text-interactive ring-brand/30">
                    {{ $wo->statusLabel() }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div>
                    <p class="phm-helper">Target</p>
                    <p class="mt-0.5 text-base font-semibold tabular-nums">
                        {{ number_format($wo->target_quantity, 0, ',', '.') }}
                    </p>
                </div>
                <div>
                    <p class="phm-helper">Ditugaskan</p>
                    <p class="mt-0.5 text-base font-semibold tabular-nums">
                        {{ number_format($wo->assignedQuantity(), 0, ',', '.') }}
                    </p>
                </div>
                <div>
                    <p class="phm-helper">Dilaporkan</p>
                    <p class="mt-0.5 text-base font-semibold tabular-nums text-interactive">
                        {{ number_format($wo->reportedQuantity(), 0, ',', '.') }}
                    </p>
                </div>
                <div>
                    <p class="phm-helper">Terverifikasi</p>
                    <p class="mt-0.5 text-base font-semibold tabular-nums text-emerald-700 dark:text-emerald-300">
                        {{ number_format($wo->verifiedQuantity(), 0, ',', '.') }}
                    </p>
                </div>
            </div>

            @if ($wo->unassignedQuantity() > 0)
                <p class="mt-3 flex items-center gap-2 text-sm text-amber-800 dark:text-amber-200">
                    <x-lucide-triangle-alert class="size-4 shrink-0" aria-hidden="true" />
                    {{ number_format($wo->unassignedQuantity(), 0, ',', '.') }} {{ $wo->product->unit }}
                    belum ditugaskan ke operator.
                </p>
            @endif

            @if ($wo->assignments->isNotEmpty())
                <div class="mt-4 border-t border-line pt-3">
                    <p class="phm-helper mb-2">Penugasan:</p>
                    <ul class="space-y-1.5">
                        @foreach ($wo->assignments as $assignment)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="min-w-0 truncate">{{ $assignment->user->full_name }}</span>
                                <span class="shrink-0 tabular-nums text-muted">
                                    {{ number_format($assignment->assigned_quantity, 0, ',', '.') }}
                                    {{ $wo->product->unit }} · {{ $assignment->statusLabel() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </article>
    @empty
        <x-empty-state icon="factory"
                       title="Belum ada work order"
                       message="Buat work order dari pesanan mitra untuk mulai merencanakan produksi." />
    @endforelse
</div>
