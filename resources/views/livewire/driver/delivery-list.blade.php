<div wire:poll.{{ config('phm.poll_seconds') * 4 }}s class="mx-auto w-full max-w-[42rem] space-y-5">

    <x-page-header title="Pengiriman Saya"
                   subtitle="Pengiriman yang ditugaskan kepada Anda hari ini." />

    @forelse ($this->shipments as $shipment)
        <article class="phm-card">
            {{-- Destination first: it is what the driver needs most. --}}
            <div class="flex items-start gap-3">
                <span class="mt-0.5 grid size-10 shrink-0 place-items-center rounded-full bg-brand/10 text-interactive">
                    <x-lucide-map-pin class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="phm-card-heading">{{ $shipment->order->partner->name }}</h2>
                    <p class="mt-1 text-base leading-relaxed text-muted">
                        {{ $shipment->destination ?? 'Alamat belum tercatat' }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 rounded-[var(--radius-control)] bg-surface-muted p-3 ring-1 ring-line">
                <div class="min-w-0">
                    <p class="phm-helper">{{ $shipment->shipment_number }}</p>
                    <p class="mt-0.5 text-base font-medium">
                        {{ $shipment->order->product->name }}
                    </p>
                    <p class="phm-helper mt-0.5">
                        {{ number_format($shipment->quantity_shipped, 0, ',', '.') }}
                        {{ $shipment->order->product->unit }}
                        @if ($shipment->delivery_note_number)
                            · surat jalan {{ $shipment->delivery_note_number }}
                        @endif
                    </p>
                </div>
                <span class="phm-badge shrink-0 {{ $shipment->statusBadgeClasses() }}">
                    {{ $shipment->statusLabel() }}
                </span>
            </div>

            @if ($shipment->notes)
                <p class="mt-3 text-sm leading-relaxed text-muted">
                    <span class="font-medium text-body">Catatan:</span> {{ $shipment->notes }}
                </p>
            @endif

            {{-- Extra input for "received" / "issue" --}}
            @if ($actionFor === $shipment->id)
                <div class="mt-4 rounded-[var(--radius-control)] border border-brand bg-brand/5 p-4">
                    @if ($pendingStatus === \App\Support\ShipmentStatus::RECEIVED)
                        <label for="recipient-{{ $shipment->id }}" class="phm-label">
                            Nama penerima barang
                        </label>
                        <input id="recipient-{{ $shipment->id }}" type="text"
                               wire:model="recipientName" class="phm-input"
                               placeholder="Contoh: Bapak Hendra" required>
                        @error('recipientName')
                            <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                        @enderror

                        <label for="note-{{ $shipment->id }}" class="phm-label mt-4">
                            Catatan <span class="ml-1 text-sm font-normal text-muted">(opsional)</span>
                        </label>
                        <textarea id="note-{{ $shipment->id }}" wire:model="actionNotes" rows="2"
                                  maxlength="1000" class="phm-textarea"></textarea>
                    @else
                        <label for="issue-{{ $shipment->id }}" class="phm-label">
                            Apa kendalanya?
                        </label>
                        <textarea id="issue-{{ $shipment->id }}" wire:model="actionNotes" rows="3"
                                  maxlength="1000" class="phm-textarea"
                                  placeholder="Contoh: Kendaraan mogok di jalan, estimasi terlambat 2 jam."
                                  required></textarea>
                        @error('actionNotes')
                            <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    @endif

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row-reverse">
                        <button type="button" wire:click="confirmAction" wire:loading.attr="disabled"
                                class="phm-btn phm-btn-primary flex-1">
                            Simpan
                        </button>
                        <button type="button" wire:click="cancelAction"
                                class="phm-btn phm-btn-secondary sm:w-32">
                            Batal
                        </button>
                    </div>
                </div>
            @else
                {{-- Only valid next transitions are offered. --}}
                <div class="mt-4 flex flex-col gap-2.5">
                    @foreach ($shipment->allowedNextStatuses() as $next)
                        @continue($next === \App\Support\ShipmentStatus::CANCELLED)
                        <button type="button"
                                wire:click="startAction({{ $shipment->id }}, '{{ $next }}')"
                                wire:loading.attr="disabled"
                                @class([
                                    'phm-btn w-full',
                                    'phm-btn-primary' => $next !== \App\Support\ShipmentStatus::ISSUE,
                                    'phm-btn-danger' => $next === \App\Support\ShipmentStatus::ISSUE,
                                ])>
                            @if ($next === \App\Support\ShipmentStatus::IN_TRANSIT)
                                <x-lucide-truck class="size-5" aria-hidden="true" />
                            @elseif ($next === \App\Support\ShipmentStatus::ARRIVED)
                                <x-lucide-map-pin class="size-5" aria-hidden="true" />
                            @elseif ($next === \App\Support\ShipmentStatus::RECEIVED)
                                <x-lucide-circle-check class="size-5" aria-hidden="true" />
                            @else
                                <x-lucide-triangle-alert class="size-5" aria-hidden="true" />
                            @endif
                            {{ \App\Support\ShipmentStatus::actionLabel($next) }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Journey so far --}}
            @if ($shipment->events->isNotEmpty())
                <details class="mt-4 border-t border-line pt-3">
                    <summary class="flex min-h-11 cursor-pointer items-center gap-2 text-sm font-semibold text-interactive">
                        Riwayat perjalanan ({{ $shipment->events->count() }})
                    </summary>
                    <ul class="mt-2 space-y-2">
                        @foreach ($shipment->events as $event)
                            <li class="flex items-start gap-2.5 text-sm">
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand" aria-hidden="true"></span>
                                <span class="min-w-0">
                                    <span class="font-medium">{{ $event->statusLabel() }}</span>
                                    <span class="block text-muted">
                                        {{ $event->occurred_at->translatedFormat('d M Y · H:i') }}
                                        @if ($event->recipient_name)
                                            · diterima {{ $event->recipient_name }}
                                        @endif
                                    </span>
                                    @if ($event->notes)
                                        <span class="block text-muted">{{ $event->notes }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </article>
    @empty
        <x-empty-state icon="truck"
                       title="Tidak ada pengiriman aktif"
                       message="Belum ada pengiriman yang ditugaskan kepada Anda. Halaman ini memperbarui sendiri bila ada tugas baru." />
    @endforelse
</div>
