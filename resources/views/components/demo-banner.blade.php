{{--
    Honest labelling: while PHM_DEMO_MODE is true, nothing on screen comes from
    a PHM production system. This banner must not be removed in demo mode.
--}}
<div class="mb-5 flex items-start gap-3 rounded-[var(--radius-card)] border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-100">
    <x-lucide-triangle-alert class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
    <p class="text-sm leading-relaxed">
        <span class="font-semibold">Mode Demo.</span>
        Semua data di aplikasi ini adalah data sintetis untuk keperluan prototipe.
        Belum ada koneksi ke ERP, INDIGO, DPR/MPR, atau database produksi PHM.
    </p>
</div>
