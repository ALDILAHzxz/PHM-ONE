<div class="mx-auto w-full max-w-[45rem]">

    @if ($this->shippableOrders->isEmpty() && $step === 'form')
        <x-empty-state icon="truck"
                       title="Tidak ada barang siap dikirim"
                       message="Semua pesanan aktif sudah dikirim penuh, atau produksinya belum diverifikasi supervisor.">
            <a href="{{ route('shipments.index') }}" class="phm-btn phm-btn-secondary mt-2">
                Kembali ke Pengiriman
            </a>
        </x-empty-state>

    {{-- ============================ SUCCESS ============================ --}}
    @elseif ($step === 'success' && $this->savedShipment)
        <div class="phm-card text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">
                <x-lucide-circle-check class="size-9" aria-hidden="true" />
            </span>

            <h2 class="phm-section-heading mt-4">Pengiriman Berhasil Dicatat</h2>
            <p class="mt-2 text-base text-muted">
                {{ number_format($this->savedShipment->quantity_shipped, 0, ',', '.') }}
                {{ $this->savedShipment->order->product->unit }}
                disiapkan untuk {{ $this->savedShipment->order->partner->name }}.
            </p>

            <dl class="mt-5 text-left">
                <x-info-row label="Nomor pengiriman" :value="$this->savedShipment->shipment_number" strong />
                <x-info-row label="Surat jalan" :value="$this->savedShipment->delivery_note_number ?? '—'" />
                <x-info-row label="Driver" :value="$this->savedShipment->driver?->full_name ?? 'Belum ditentukan'" />
                <x-info-row label="Status" :value="$this->savedShipment->statusLabel()" />
            </dl>

            <p class="phm-helper mt-4">
                Driver akan memperbarui status perjalanan dari halaman Pengiriman Saya.
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <button type="button" wire:click="recordAnother" class="phm-btn phm-btn-primary">
                    Catat Pengiriman Lain
                </button>
                <a href="{{ route('shipments.index') }}" class="phm-btn phm-btn-secondary">
                    Lihat Daftar Pengiriman
                </a>
            </div>
        </div>

    {{-- ========================= CONFIRMATION ========================== --}}
    @elseif ($step === 'confirm' && $this->order)
        <div class="phm-card">
            <h2 class="phm-section-heading">Periksa Data Pengiriman</h2>
            <p class="phm-helper mt-1">Belum disimpan. Pastikan datanya sudah benar.</p>

            <dl class="mt-5">
                <x-info-row label="Mitra" :value="$this->order->partner->name" strong />
                <x-info-row label="Pesanan" :value="$this->order->order_number . ' · PO ' . $this->order->po_number" />
                <x-info-row label="Barang" :value="$this->order->product->name" />
                <x-info-row label="Jumlah dikirim"
                            :value="number_format((int) $quantity, 0, ',', '.') . ' ' . $this->order->product->unit"
                            strong />
                <x-info-row label="Surat jalan" :value="$deliveryNote ?: 'Dibuat otomatis'" />
                <x-info-row label="Driver"
                            :value="$this->drivers->firstWhere('id', $driverId)?->full_name ?? 'Belum ditentukan'" />
                @if (filled($notes))
                    <x-info-row label="Catatan" :value="$notes" />
                @endif
            </dl>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row-reverse">
                <button type="button" wire:click="save" wire:loading.attr="disabled"
                        class="phm-btn phm-btn-primary flex-1">
                    <span wire:loading.remove wire:target="save">Ya, Simpan Pengiriman</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <x-lucide-loader-circle class="size-5 animate-spin" aria-hidden="true" />
                        Menyimpan…
                    </span>
                </button>
                <button type="button" wire:click="backToForm" class="phm-btn phm-btn-secondary sm:w-40">
                    Perbaiki
                </button>
            </div>
        </div>

    {{-- ============================= FORM ============================== --}}
    @else
        <form wire:submit="review" class="space-y-5">
            <div class="phm-card">
                <h1 class="phm-page-title">Catat Pengiriman</h1>
                <p class="mt-2 text-base text-muted">Catat barang yang dikirim ke mitra.</p>
                <div class="mt-4 h-1 w-16 rounded-full bg-brand" aria-hidden="true"></div>
            </div>

            {{-- Order selection --}}
            <section class="phm-card">
                <h2 class="phm-card-heading">Pesanan</h2>

                <label for="order" class="phm-label mt-4">
                    Pilih pesanan <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                </label>
                <select id="order" wire:model.live="orderId" class="phm-input" required>
                    <option value="">— Pilih pesanan —</option>
                    @foreach ($this->shippableOrders as $option)
                        <option value="{{ $option->id }}">
                            {{ $option->partner->name }} · {{ $option->order_number }} ·
                            sisa {{ number_format($option->remainingToShipQuantity(), 0, ',', '.') }} {{ $option->product->unit }}
                        </option>
                    @endforeach
                </select>
                @error('orderId')
                    <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                @enderror

                {{-- Read-only order context: nothing here is retyped. --}}
                @if ($this->order)
                    <dl class="mt-4">
                        <x-info-row label="Mitra" :value="$this->order->partner->name" />
                        <x-info-row label="Barang" :value="$this->order->product->name" />
                        <x-info-row label="Jumlah dipesan"
                                    :value="number_format($this->order->quantity, 0, ',', '.') . ' ' . $this->order->product->unit" />
                        <x-info-row label="Siap dikirim"
                                    :value="number_format($this->order->readyToShipQuantity(), 0, ',', '.') . ' ' . $this->order->product->unit"
                                    strong />
                        <x-info-row label="Sudah dikirim"
                                    :value="number_format($this->order->shippedQuantity(), 0, ',', '.') . ' ' . $this->order->product->unit" />
                        <x-info-row label="Sisa kirim"
                                    :value="number_format($this->order->remainingToShipQuantity(), 0, ',', '.') . ' ' . $this->order->product->unit" />
                        <x-info-row label="Tujuan" :value="$this->order->partner->address ?? '—'" />
                    </dl>

                    @if ($this->order->readyToShipQuantity() === 0)
                        <div class="mt-3 flex items-start gap-3 rounded-[var(--radius-control)] border border-amber-300 bg-amber-50 p-3 text-amber-900 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-100">
                            <x-lucide-triangle-alert class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
                            <p class="text-sm">
                                Belum ada barang siap kirim untuk pesanan ini. Hanya produksi yang
                                sudah <strong>diverifikasi supervisor</strong> yang dihitung siap kirim.
                            </p>
                        </div>
                    @endif
                @endif
            </section>

            {{-- Quantity --}}
            <section class="phm-card">
                <h2 class="phm-card-heading">
                    Jumlah Dikirim
                    <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                </h2>

                <label for="quantity" class="phm-label mt-4">Berapa unit yang dikirim?</label>
                <div class="relative">
                    <input id="quantity" type="number" inputmode="numeric" step="1" min="1"
                           wire:model.blur="quantity"
                           class="phm-input pr-16 text-[1.375rem] font-semibold"
                           placeholder="0" required>
                    <span class="pointer-events-none absolute inset-y-0 right-0 grid w-16 place-items-center text-base font-medium text-muted"
                          aria-hidden="true">
                        {{ $this->order?->product->unit ?? 'pcs' }}
                    </span>
                </div>
                @error('quantity')
                    <p role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                        <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </section>

            {{-- Optional details --}}
            <section class="phm-card space-y-5">
                <h2 class="phm-card-heading">
                    Detail Pengiriman
                    <span class="ml-1 text-sm font-normal text-muted">(opsional)</span>
                </h2>

                <div>
                    <label for="note" class="phm-label">Nomor Surat Jalan</label>
                    <input id="note" type="text" wire:model.blur="deliveryNote" class="phm-input"
                           placeholder="Dibuat otomatis bila dikosongkan" spellcheck="false">
                    @error('deliveryNote')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="driver" class="phm-label">Driver</label>
                    <select id="driver" wire:model="driverId" class="phm-input">
                        <option value="">— Belum ditentukan —</option>
                        @foreach ($this->drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="notes" class="phm-label">Catatan Pengiriman</label>
                    <textarea id="notes" wire:model.blur="notes" rows="3" maxlength="1000"
                              class="phm-textarea"
                              placeholder="Tulis bila ada informasi khusus untuk driver atau mitra."></textarea>
                </div>
            </section>

            <div class="flex flex-col gap-3 sm:flex-row-reverse sm:items-center">
                <button type="submit" class="phm-btn phm-btn-primary flex-1" wire:loading.attr="disabled">
                    Periksa &amp; Simpan Pengiriman
                </button>
                <a href="{{ route('shipments.index') }}" class="phm-btn phm-btn-ghost sm:w-32">Batal</a>
            </div>
        </form>
    @endif
</div>
