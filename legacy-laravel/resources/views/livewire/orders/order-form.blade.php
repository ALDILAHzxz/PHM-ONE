<div class="mx-auto w-full max-w-[45rem]">

    {{-- ============================ SUCCESS ============================= --}}
    @if ($step === 'success' && $this->savedOrder)
        <div class="phm-card text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">
                <x-lucide-circle-check class="size-9" aria-hidden="true" />
            </span>

            <h2 class="phm-section-heading mt-4">Pesanan Berhasil Disimpan</h2>
            <p class="mt-2 text-base text-muted">
                Pesanan {{ $this->savedOrder->order_number }} sudah tercatat dan dapat
                direncanakan produksinya oleh PPIC.
            </p>

            <dl class="mt-5 text-left">
                <x-info-row label="Nomor pesanan" :value="$this->savedOrder->order_number" strong />
                <x-info-row label="Mitra" :value="$this->savedOrder->partner->name" />
                <x-info-row label="Nomor PO" :value="$this->savedOrder->po_number" />
                <x-info-row label="Barang" :value="$this->savedOrder->product->name" />
                <x-info-row label="Jumlah"
                            :value="number_format($this->savedOrder->quantity, 0, ',', '.') . ' ' . $this->savedOrder->product->unit" />
                <x-info-row label="Target pengiriman"
                            :value="$this->savedOrder->delivery_target_date->translatedFormat('l, d F Y')" />
            </dl>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <a href="{{ route('orders.show', $this->savedOrder) }}" class="phm-btn phm-btn-primary">
                    Lihat Pesanan
                </a>
                <button type="button" wire:click="addAnother" class="phm-btn phm-btn-secondary">
                    Tambah Lagi
                </button>
                <a href="{{ route('orders.index') }}" class="phm-btn phm-btn-secondary">
                    Daftar Pesanan
                </a>
            </div>
        </div>

    {{-- ========================= CONFIRMATION ========================== --}}
    @elseif ($step === 'confirm')
        <div class="phm-card">
            <h2 class="phm-section-heading">Periksa Data Pesanan</h2>
            <p class="phm-helper mt-1">Belum disimpan. Pastikan datanya sudah benar.</p>

            <dl class="mt-5">
                <x-info-row label="Nama mitra" :value="$this->selectedPartner?->name" strong />
                <x-info-row label="Nomor PO" :value="$poNumber" />
                <x-info-row label="Nama barang" :value="$this->selectedProduct?->name" />
                <x-info-row label="Jumlah pesanan"
                            :value="number_format((int) $quantity, 0, ',', '.') . ' ' . ($this->selectedProduct?->unit ?? 'pcs')"
                            strong />
                <x-info-row label="Target pengiriman"
                            :value="\Illuminate\Support\Carbon::parse($deliveryTargetDate)->translatedFormat('l, d F Y')" />
                @if (filled($notes))
                    <x-info-row label="Catatan" :value="$notes" />
                @endif
            </dl>

            <p class="mt-5 text-base font-medium">Apakah data pesanan sudah sesuai?</p>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row-reverse">
                <button type="button" wire:click="save" wire:loading.attr="disabled"
                        class="phm-btn phm-btn-primary flex-1">
                    <span wire:loading.remove wire:target="save">Ya, Simpan Pesanan</span>
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
                <h1 class="phm-page-title">Tambah Pesanan</h1>
                <p class="mt-2 text-base text-muted">Catat pesanan baru dari mitra.</p>
                <div class="mt-4 h-1 w-16 rounded-full bg-brand" aria-hidden="true"></div>
            </div>

            <section class="phm-card space-y-5">
                {{-- 1. Nama Mitra --}}
                <div>
                    <label for="partner" class="phm-label">
                        Nama Mitra <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                    </label>
                    <select id="partner" wire:model.live="partnerId" class="phm-input" required>
                        <option value="">— Pilih mitra —</option>
                        @foreach ($this->partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                        @endforeach
                    </select>
                    @error('partnerId')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror

                    {{-- Known partner details appear automatically. --}}
                    @if ($this->selectedPartner)
                        <div class="mt-3 rounded-[var(--radius-control)] bg-surface-muted p-3 ring-1 ring-line">
                            <p class="phm-helper">
                                <span class="font-medium text-body">{{ $this->selectedPartner->contact_person ?? 'Kontak belum tercatat' }}</span>
                                @if ($this->selectedPartner->phone)
                                    · {{ $this->selectedPartner->phone }}
                                @endif
                            </p>
                            @if ($this->selectedPartner->address)
                                <p class="phm-helper mt-0.5">{{ $this->selectedPartner->address }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- 2. Nomor PO --}}
                <div>
                    <label for="po" class="phm-label">
                        Nomor PO <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                    </label>
                    <input id="po" type="text" wire:model.blur="poNumber" class="phm-input"
                           placeholder="PO-MMS-2601" spellcheck="false" required>
                    @error('poNumber')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                    <p class="phm-helper mt-2">Nomor PO sesuai dokumen dari mitra.</p>
                </div>

                {{-- 3. Nama Barang --}}
                <div>
                    <label for="product" class="phm-label">
                        Nama Barang <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                    </label>
                    <select id="product" wire:model.live="productId" class="phm-input" required>
                        <option value="">— Pilih barang —</option>
                        @foreach ($this->products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                    @error('productId')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                    @if ($this->selectedProduct?->description)
                        <p class="phm-helper mt-2">{{ $this->selectedProduct->description }}</p>
                    @endif
                </div>

                {{-- 4. Jumlah Pesanan --}}
                <div>
                    <label for="quantity" class="phm-label">
                        Jumlah Pesanan <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                    </label>
                    <div class="relative">
                        <input id="quantity" type="number" inputmode="numeric" step="1" min="1"
                               wire:model.blur="quantity" class="phm-input pr-16 font-semibold"
                               placeholder="0" required>
                        <span class="pointer-events-none absolute inset-y-0 right-0 grid w-16 place-items-center text-base font-medium text-muted"
                              aria-hidden="true">
                            {{ $this->selectedProduct?->unit ?? 'pcs' }}
                        </span>
                    </div>
                    @error('quantity')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 5. Target Pengiriman --}}
                <div>
                    <label for="target" class="phm-label">
                        Target Pengiriman <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                    </label>
                    <input id="target" type="date" wire:model.blur="deliveryTargetDate"
                           class="phm-input" :min="today()->toDateString()" required>
                    @error('deliveryTargetDate')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                {{-- optional --}}
                <div>
                    <label for="notes" class="phm-label">
                        Catatan <span class="ml-1 text-sm font-normal text-muted">(opsional)</span>
                    </label>
                    <textarea id="notes" wire:model.blur="notes" rows="3" maxlength="1000"
                              class="phm-textarea"
                              placeholder="Tulis bila ada permintaan khusus dari mitra."></textarea>
                    @error('notes')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <div class="flex flex-col gap-3 sm:flex-row-reverse sm:items-center">
                <button type="submit" class="phm-btn phm-btn-primary flex-1" wire:loading.attr="disabled">
                    Periksa &amp; Simpan Pesanan
                </button>
                <a href="{{ route('orders.index') }}" class="phm-btn phm-btn-ghost sm:w-32">Batal</a>
            </div>
        </form>
    @endif
</div>
