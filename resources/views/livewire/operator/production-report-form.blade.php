{{--
    Google-Forms-style layout (master specification section 14):
    single centered column, max 720px, separate stacked cards, no sidebar,
    no tables, no charts, one dominant submit action.
--}}
<div class="mx-auto w-full max-w-[45rem]">

    {{-- =====================================================================
         NO ASSIGNMENT — honest empty state, not a broken form.
    ====================================================================== --}}
    @if ($assignments->isEmpty())
        <x-empty-state icon="clipboard-list"
                       title="Belum ada pekerjaan aktif"
                       message="Saat ini tidak ada penugasan yang bisa Anda catat. Hubungi atasan atau PPIC bila Anda merasa seharusnya ada pekerjaan hari ini.">
            <a href="{{ route('operator.tasks') }}" class="phm-btn phm-btn-secondary mt-2">
                Kembali ke Tugas Saya
            </a>
        </x-empty-state>

    {{-- =====================================================================
         ASSIGNMENT CHOOSER — only shown when there is genuinely a choice.
    ====================================================================== --}}
    @elseif ($assignment === null)
        <div class="phm-card">
            <h2 class="phm-card-heading">Pilih Pekerjaan</h2>
            <p class="phm-helper mt-1">Anda memiliki {{ $assignments->count() }} penugasan aktif. Pilih salah satu untuk dicatat.</p>

            <div class="mt-4 space-y-3">
                @foreach ($assignments as $option)
                    <button type="button"
                            wire:click="selectAssignment({{ $option->id }})"
                            class="flex w-full items-center gap-3 rounded-[var(--radius-control)] border border-line bg-surface p-4 text-left transition-colors duration-150 hover:border-brand hover:bg-brand/5">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-base font-semibold">{{ $option->productName() }}</span>
                            <span class="block truncate text-sm text-muted">
                                {{ $option->woNumber() }} · sisa {{ number_format($option->remainingQty(), 0, ',', '.') }} {{ $option->unit() }}
                            </span>
                        </span>
                        <x-lucide-chevron-right class="size-5 shrink-0 text-muted" aria-hidden="true" />
                    </button>
                @endforeach
            </div>

            @error('assignmentId')
                <p role="alert" class="mt-3 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
            @enderror
        </div>

    {{-- =====================================================================
         SUCCESS — rendered only after the report was committed.
    ====================================================================== --}}
    @elseif ($step === 'success' && $savedReport)
        <div class="phm-card text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">
                <x-lucide-circle-check class="size-9" aria-hidden="true" />
            </span>

            <h2 class="phm-section-heading mt-4">Hasil Berhasil Dicatat</h2>
            <p class="mt-2 text-base text-muted">
                {{ number_format($savedReport->quantity_completed, 0, ',', '.') }}
                {{ $savedReport->assignment->unit() }}
                telah masuk ke laporan pekerjaan Anda.
            </p>

            <div class="mt-4 flex flex-col items-center gap-2">
                <span class="phm-badge {{ $savedReport->statusBadgeClasses() }}">
                    <x-lucide-clock class="size-3.5" aria-hidden="true" />
                    {{ $savedReport->statusLabel() }}
                </span>
                <p class="phm-helper">
                    Nomor laporan {{ $savedReport->report_number }} ·
                    {{ $savedReport->submitted_at->translatedFormat('d M Y, H:i') }}
                </p>
            </div>

            @if ($savedReport->is_exception)
                <div class="mt-4 flex items-start gap-3 rounded-[var(--radius-control)] border border-amber-300 bg-amber-50 p-3 text-left text-amber-900 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-100">
                    <x-lucide-triangle-alert class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
                    <p class="text-sm">
                        Laporan ini melebihi sisa target dan dicatat sebagai pengecualian.
                        Atasan akan memeriksanya secara khusus.
                    </p>
                </div>
            @endif

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <a href="{{ route('operator.tasks') }}" class="phm-btn phm-btn-primary">
                    Kembali ke Tugas Saya
                </a>
                <button type="button" wire:click="recordAnother" class="phm-btn phm-btn-secondary">
                    Catat Lagi
                </button>
                <a href="{{ route('operator.history') }}" class="phm-btn phm-btn-secondary">
                    Lihat Riwayat
                </a>
            </div>
        </div>

    {{-- =====================================================================
         CONFIRMATION — plain summary, nothing saved yet.
    ====================================================================== --}}
    @elseif ($step === 'confirm')
        <div class="phm-card">
            <h2 class="phm-section-heading">Periksa Hasil Pekerjaan</h2>
            <p class="phm-helper mt-1">Belum disimpan. Pastikan datanya sudah benar.</p>

            <dl class="mt-5">
                <x-info-row label="Produk" :value="$assignment->productName()" />
                <x-info-row label="Nomor pekerjaan" :value="$assignment->woNumber()" />
                <x-info-row label="Jumlah selesai"
                            :value="number_format((int) $quantity, 0, ',', '.') . ' ' . $assignment->unit()"
                            strong />
                <x-info-row label="Kendala">
                    {{ $issueState === 'ada' ? ($issueOptions[$issueCategory] ?? 'Ada kendala') : 'Tidak ada' }}
                </x-info-row>
                @if ($issueState === 'ada' && filled($issueDescription))
                    <x-info-row label="Penjelasan kendala" :value="$issueDescription" />
                @endif
                @if (filled($additionalNotes))
                    <x-info-row label="Keterangan tambahan" :value="$additionalNotes" />
                @endif
            </dl>

            <p class="mt-5 text-base font-medium">Apakah hasil pekerjaan sudah sesuai?</p>

            @error('quantity')
                <p role="alert" class="mt-3 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                    <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <span>{{ $message }}</span>
                </p>
            @enderror
            @error('assignmentId')
                <p role="alert" class="mt-3 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                    <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <span>{{ $message }}</span>
                </p>
            @enderror

            <div class="mt-5 flex flex-col gap-3 sm:flex-row-reverse">
                <button type="button"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        class="phm-btn phm-btn-primary flex-1">
                    <span wire:loading.remove wire:target="save">Ya, Simpan Hasil</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <x-lucide-loader-circle class="size-5 animate-spin" aria-hidden="true" />
                        Menyimpan…
                    </span>
                </button>
                <button type="button"
                        wire:click="backToForm"
                        wire:loading.attr="disabled"
                        class="phm-btn phm-btn-secondary sm:w-40">
                    Perbaiki
                </button>
            </div>
        </div>

    {{-- =====================================================================
         THE FORM
    ====================================================================== --}}
    @else
        <form wire:submit="review" class="space-y-5">

            {{-- Form header --}}
            <div class="phm-card">
                <h1 class="phm-page-title">Catat Hasil Produksi</h1>
                <p class="mt-2 text-base text-muted">Isi hasil pekerjaan Anda hari ini.</p>
                <div class="mt-4 h-1 w-16 rounded-full bg-brand" aria-hidden="true"></div>
            </div>

            {{-- CARD 1 — read-only job information, nothing to retype --}}
            <section class="phm-card" aria-labelledby="judul-informasi">
                <h2 id="judul-informasi" class="phm-card-heading">Informasi Pekerjaan</h2>
                <p class="phm-helper mt-1">Data ini diambil otomatis dari penugasan Anda.</p>

                <dl class="mt-4">
                    <x-info-row label="Nama produk" :value="$assignment->productName()" />
                    <x-info-row label="Nomor pekerjaan" :value="$assignment->woNumber()" />
                    <x-info-row label="Nama petugas" :value="auth()->user()->full_name" />
                    @if ($assignment->stage)
                        <x-info-row label="Tahap" :value="$assignment->stage->name" />
                    @endif
                    <x-info-row label="Target"
                                :value="number_format($assignment->targetQty(), 0, ',', '.') . ' ' . $assignment->unit()" />
                    <x-info-row label="Sudah tercatat"
                                :value="number_format($assignment->reportedQty(), 0, ',', '.') . ' ' . $assignment->unit()" />
                    <x-info-row label="Sisa target"
                                :value="number_format($assignment->remainingQty(), 0, ',', '.') . ' ' . $assignment->unit()"
                                strong />
                </dl>

                @if ($assignments->count() > 1)
                    <button type="button"
                            wire:click="$set('assignmentId', null)"
                            class="phm-btn phm-btn-ghost mt-3 -ml-3 min-h-11 px-3 text-base">
                        <x-lucide-refresh-cw class="size-4" aria-hidden="true" />
                        Ganti pekerjaan
                    </button>
                @endif

                @error('assignmentId')
                    <p role="alert" class="mt-3 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                        <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </section>

            {{-- CARD 2 — the one number the operator actually types --}}
            <section class="phm-card" aria-labelledby="judul-hasil">
                <h2 id="judul-hasil" class="phm-card-heading">
                    Hasil Pekerjaan
                    <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                </h2>

                <label for="quantity" class="phm-label mt-4">
                    Berapa unit yang selesai pada pencatatan ini?
                </label>

                <div class="relative">
                    <input id="quantity"
                           type="number"
                           inputmode="numeric"
                           step="1"
                           min="1"
                           wire:model.blur="quantity"
                           class="phm-input pr-16 text-[1.375rem] font-semibold"
                           placeholder="0"
                           required
                           aria-describedby="quantity-help @error('quantity') quantity-error @enderror"
                           @error('quantity') aria-invalid="true" @enderror>
                    <span class="pointer-events-none absolute inset-y-0 right-0 grid w-16 place-items-center text-base font-medium text-muted"
                          aria-hidden="true">
                        {{ $assignment->unit() }}
                    </span>
                </div>

                @error('quantity')
                    <p id="quantity-error" role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                        <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror

                <p id="quantity-help" class="phm-helper mt-2">
                    Masukkan <strong>tambahan</strong> unit yang selesai, bukan jumlah keseluruhan.
                    Sisa target Anda saat ini {{ number_format($assignment->remainingQty(), 0, ',', '.') }} {{ $assignment->unit() }}.
                </p>
            </section>

            {{-- CARD 3 — conditional: nothing extra appears unless there IS an issue --}}
            <section class="phm-card" aria-labelledby="judul-kendala">
                <h2 id="judul-kendala" class="phm-card-heading">Kendala Pekerjaan</h2>

                <fieldset class="mt-4">
                    <legend class="phm-label">Apakah ada kendala?</legend>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (['tidak' => 'Tidak ada', 'ada' => 'Ada kendala'] as $value => $label)
                            <label @class([
                                'flex min-h-[3.5rem] cursor-pointer items-center gap-3 rounded-[var(--radius-control)] border px-4 transition-colors duration-150',
                                'border-brand bg-brand/8 ring-1 ring-brand' => $issueState === $value,
                                'border-line-strong hover:border-brand' => $issueState !== $value,
                            ])>
                                <input type="radio"
                                       wire:model.live="issueState"
                                       value="{{ $value }}"
                                       name="issueState"
                                       class="size-5 shrink-0 border-line-strong text-brand focus:ring-2 focus:ring-brand/40">
                                <span class="text-base font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                @if ($issueState === 'ada')
                    <div class="mt-5 space-y-5 border-t border-line pt-5">
                        <fieldset>
                            <legend class="phm-label">
                                Jenis Kendala
                                <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                            </legend>

                            <div class="space-y-2">
                                @foreach ($issueOptions as $value => $label)
                                    <label @class([
                                        'flex min-h-[3.25rem] cursor-pointer items-center gap-3 rounded-[var(--radius-control)] border px-4 transition-colors duration-150',
                                        'border-brand bg-brand/8' => $issueCategory === $value,
                                        'border-line hover:border-brand' => $issueCategory !== $value,
                                    ])>
                                        <input type="radio"
                                               wire:model.live="issueCategory"
                                               value="{{ $value }}"
                                               name="issueCategory"
                                               class="size-5 shrink-0 border-line-strong text-brand focus:ring-2 focus:ring-brand/40">
                                        <span class="text-base">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>

                            @error('issueCategory')
                                <p role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                                    <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </fieldset>

                        <div>
                            <label for="issueDescription" class="phm-label">
                                Jelaskan Kendala
                                <span class="text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
                            </label>
                            <textarea id="issueDescription"
                                      wire:model.blur="issueDescription"
                                      rows="4"
                                      maxlength="1000"
                                      class="phm-textarea"
                                      placeholder="Contoh: Mesin las mati sekitar 30 menit sehingga hasil berkurang."
                                      @error('issueDescription') aria-invalid="true" aria-describedby="issue-error" @enderror></textarea>

                            @error('issueDescription')
                                <p id="issue-error" role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                                    <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>
                    </div>
                @endif
            </section>

            {{-- CARD 4 — optional, never required for a normal report --}}
            <section class="phm-card" aria-labelledby="judul-keterangan">
                <h2 id="judul-keterangan" class="phm-card-heading">
                    Keterangan Tambahan
                    <span class="ml-1 text-sm font-normal text-muted">(opsional)</span>
                </h2>

                <label for="additionalNotes" class="sr-only">Keterangan tambahan</label>
                <textarea id="additionalNotes"
                          wire:model.blur="additionalNotes"
                          rows="3"
                          maxlength="1000"
                          class="phm-textarea mt-4"
                          placeholder="Tulis jika ada informasi lain yang perlu disampaikan."></textarea>

                @error('additionalNotes')
                    <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </section>

            {{-- ONE dominant submit action; cancel stays visually subordinate --}}
            <div class="flex flex-col gap-3 sm:flex-row-reverse sm:items-center">
                <button type="submit" class="phm-btn phm-btn-primary flex-1" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="review">Periksa &amp; Simpan Hasil</span>
                    <span wire:loading wire:target="review" class="flex items-center gap-2">
                        <x-lucide-loader-circle class="size-5 animate-spin" aria-hidden="true" />
                        Memeriksa…
                    </span>
                </button>
                <a href="{{ route('operator.tasks') }}" class="phm-btn phm-btn-ghost sm:w-32">
                    Batal
                </a>
            </div>
        </form>
    @endif
</div>
