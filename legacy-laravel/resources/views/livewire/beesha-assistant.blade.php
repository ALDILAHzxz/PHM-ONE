{{--
    Global floating Beesha AI (master specification section 11).

    Position: fixed bottom-right, above ordinary content but BELOW the
    navigation drawer overlay (drawer uses z-60, this uses z-40/z-50).
    The FAB hides while the panel is open so it never covers the conversation.
--}}
<div>
    {{-- ===================== FLOATING ACTION BUTTON ====================== --}}
    <div x-cloak
         x-show="!$wire.open"
         x-transition.opacity.duration.150ms
         class="fixed right-5 bottom-6 z-40 md:right-6 md:bottom-8"
         style="padding-bottom: env(safe-area-inset-bottom);">
        <button type="button"
                wire:click="toggle"
                class="group flex h-14 items-center gap-2.5 rounded-full bg-brand pr-5 pl-4 text-white shadow-[0_8px_24px_rgba(0,119,200,0.4)] transition-all duration-200 hover:bg-[#0069b0] hover:shadow-[0_10px_28px_rgba(0,119,200,0.5)] active:scale-[0.97] motion-reduce:active:scale-100 max-sm:w-14 max-sm:justify-center max-sm:pr-4"
                aria-label="Buka Beesha AI — tanya tentang pekerjaan Anda"
                title="Tanya Beesha">
            <x-lucide-bot class="size-7 shrink-0" aria-hidden="true" />
            {{-- Label only on wider screens, where it aids discovery without clutter. --}}
            <span class="text-[1.0625rem] font-semibold max-sm:hidden">Tanya Beesha</span>
        </button>
    </div>

    {{-- ========================== CHAT PANEL ============================ --}}
    <div x-cloak x-show="$wire.open" class="relative z-50" role="dialog" aria-modal="true" aria-label="Beesha AI">

        {{-- Mobile-only backdrop: on desktop the drawer sits beside the page. --}}
        <div x-show="$wire.open"
             x-transition.opacity.duration.200ms
             class="fixed inset-0 bg-slate-900/50 md:hidden"
             wire:click="close"
             aria-hidden="true"></div>

        <section x-show="$wire.open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full md:translate-y-0 md:translate-x-full"
                 x-transition:enter-end="translate-y-0 md:translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0 md:translate-x-0"
                 x-transition:leave-end="translate-y-full md:translate-y-0 md:translate-x-full"
                 @keydown.escape="$wire.close()"
                 class="fixed inset-x-0 bottom-0 top-16 flex flex-col bg-surface shadow-2xl md:inset-y-0 md:right-0 md:left-auto md:top-0 md:w-[28rem] md:border-l md:border-line lg:w-[30rem]">

            {{-- Persistent header --}}
            <header class="flex items-center gap-3 border-b border-line bg-surface px-4 py-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand text-white">
                    <x-lucide-bot class="size-6" aria-hidden="true" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-semibold">Beesha</p>
                    <p class="truncate text-[0.8125rem] text-muted">
                        @if ($this->isDemoAssistant)
                            Asisten demo · menjawab dari database PHM One
                        @else
                            Asisten AI · terhubung ke layanan model
                        @endif
                    </p>
                </div>

                @if ($this->messages->isNotEmpty())
                    <button type="button"
                            wire:click="clearConversation"
                            class="grid size-11 place-items-center rounded-[0.6875rem] text-muted transition-colors hover:bg-brand/10 hover:text-interactive"
                            aria-label="Hapus riwayat percakapan"
                            title="Hapus percakapan">
                        <x-lucide-trash-2 class="size-5" aria-hidden="true" />
                    </button>
                @endif

                <button type="button"
                        wire:click="close"
                        class="grid size-11 place-items-center rounded-[0.6875rem] text-muted transition-colors hover:bg-brand/10 hover:text-interactive"
                        aria-label="Tutup Beesha AI">
                    <x-lucide-x class="size-6" aria-hidden="true" />
                </button>
            </header>

            {{-- Honest capability note while running on the demo assistant --}}
            @if ($this->isDemoAssistant)
                <p class="border-b border-amber-300 bg-amber-50 px-4 py-2.5 text-[0.8125rem] leading-relaxed text-amber-900 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-100">
                    Beesha versi demo menjawab langsung dari data PHM One tanpa mengirim apa pun
                    ke layanan luar. Jawaban terbatas pada pertanyaan seputar pekerjaan Anda.
                </p>
            @endif

            {{-- Conversation --}}
            <div class="phm-scroll flex-1 space-y-4 overflow-y-auto px-4 py-4"
                 x-data="{
                     bottom() {
                         this.$nextTick(() => { this.$el.scrollTop = this.$el.scrollHeight; });
                     },
                 }"
                 x-init="bottom()"
                 @beesha-scroll-bottom.window="bottom()"
                 aria-live="polite"
                 aria-atomic="false">

                @if ($this->messages->isEmpty())
                    <div class="py-6 text-center">
                        <p class="phm-card-heading">Ada yang bisa saya bantu?</p>
                        <p class="phm-helper mt-2">
                            Tanyakan tentang tugas, hasil produksi, pesanan, atau pengiriman
                            yang menjadi tanggung jawab Anda.
                        </p>
                    </div>
                @endif

                @foreach ($this->messages as $message)
                    @if ($message->isFromUser())
                        <div class="flex justify-end">
                            <p class="max-w-[85%] rounded-2xl rounded-br-md bg-brand px-4 py-2.5 text-base leading-relaxed text-white">
                                {{ $message->content }}
                            </p>
                        </div>
                    @else
                        <div class="flex gap-2.5">
                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-brand/10 text-interactive">
                                <x-lucide-bot class="size-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 max-w-[85%]">
                                <div class="rounded-2xl rounded-bl-md bg-surface-muted px-4 py-3 text-base leading-relaxed ring-1 ring-line">
                                    {{-- Minimal, escaped formatting: **bold** and line breaks only. --}}
                                    {!! \App\Support\ChatMarkup::render($message->content) !!}
                                </div>
                                @if ($message->tool_used)
                                    <p class="mt-1.5 flex items-center gap-1.5 text-[0.75rem] text-muted">
                                        <x-lucide-database class="size-3.5 shrink-0" aria-hidden="true" />
                                        Dari data PHM One ({{ $message->tool_used }})
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach

                <div wire:loading wire:target="send,askSuggestion" class="flex gap-2.5">
                    <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-brand/10 text-interactive">
                        <x-lucide-bot class="size-5" aria-hidden="true" />
                    </span>
                    <p class="flex items-center gap-2 rounded-2xl rounded-bl-md bg-surface-muted px-4 py-3 text-base text-muted ring-1 ring-line">
                        <x-lucide-loader-circle class="size-4 animate-spin" aria-hidden="true" />
                        Beesha sedang memeriksa data…
                    </p>
                </div>
            </div>

            {{-- Quick questions: collapsible, and collapsed automatically once
                 the user has asked something, to give the answer more space. --}}
            @if (count($this->suggestions) > 0)
                <div class="border-t border-line px-4 py-3">
                    <button type="button"
                            wire:click="$toggle('showSuggestions')"
                            class="flex min-h-9 w-full items-center justify-between gap-2 text-sm font-semibold text-interactive"
                            :aria-expanded="'{{ $showSuggestions ? 'true' : 'false' }}'">
                        <span>Pertanyaan cepat</span>
                        @if ($showSuggestions)
                            <x-lucide-chevron-down class="size-4" aria-hidden="true" />
                        @else
                            <x-lucide-chevron-up class="size-4" aria-hidden="true" />
                        @endif
                    </button>

                    @if ($showSuggestions)
                        <div class="mt-2.5 flex flex-wrap gap-2">
                            @foreach ($this->suggestions as $suggestion)
                                <button type="button"
                                        wire:click="askSuggestion(@js($suggestion))"
                                        wire:loading.attr="disabled"
                                        class="rounded-full border border-line-strong px-3 py-2 text-left text-[0.8125rem] transition-colors duration-150 hover:border-brand hover:bg-brand/5 hover:text-interactive">
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Input stays at the bottom, above the mobile keyboard. --}}
            <form wire:submit="send"
                  class="border-t border-line bg-surface px-4 py-3"
                  style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
                <div class="flex items-end gap-2">
                    <label for="beesha-question" class="sr-only">Pertanyaan untuk Beesha</label>
                    <textarea id="beesha-question"
                              wire:model="question"
                              rows="1"
                              maxlength="500"
                              class="phm-textarea max-h-32 min-h-[3rem] flex-1 resize-none py-3"
                              placeholder="Tulis pertanyaan Anda…"
                              @keydown.enter.prevent="$wire.send()"></textarea>

                    <button type="submit"
                            class="grid size-12 shrink-0 place-items-center rounded-[var(--radius-control)] bg-brand text-white transition-colors duration-200 hover:bg-[#0069b0] disabled:opacity-60"
                            wire:loading.attr="disabled"
                            aria-label="Kirim pertanyaan">
                        <x-lucide-send class="size-5" aria-hidden="true" />
                    </button>
                </div>

                @error('question')
                    <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </form>
        </section>
    </div>
</div>
