@if (session('status') || session('error'))
    <div class="mb-5 space-y-3">
        @if (session('status'))
            <div role="status"
                 class="flex items-start gap-3 rounded-[var(--radius-card)] border border-emerald-300 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-400/40 dark:bg-emerald-400/10 dark:text-emerald-100">
                <x-lucide-circle-check class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
                <p class="text-sm leading-relaxed">{{ session('status') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div role="alert"
                 class="flex items-start gap-3 rounded-[var(--radius-card)] border border-rose-300 bg-rose-50 p-4 text-rose-900 dark:border-rose-400/40 dark:bg-rose-400/10 dark:text-rose-100">
                <x-lucide-circle-alert class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
                <p class="text-sm leading-relaxed">{{ session('error') }}</p>
            </div>
        @endif
    </div>
@endif
