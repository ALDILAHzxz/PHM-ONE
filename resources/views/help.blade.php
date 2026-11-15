<x-app-layout title="Bantuan" page-title="Bantuan">
    <div class="mx-auto w-full max-w-[45rem] space-y-5">
        <x-page-header title="Bantuan"
                       subtitle="Panduan singkat untuk pekerjaan Anda di PHM One." />

        @forelse ($guides as $guide)
            <section class="phm-card">
                <h2 class="phm-card-heading">{{ $guide['title'] }}</h2>
                <ol class="mt-3 space-y-2.5">
                    @foreach ($guide['steps'] as $index => $step)
                        <li class="flex gap-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand/10 text-[0.8125rem] font-semibold text-interactive">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-base leading-relaxed">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @empty
            <x-empty-state icon="circle-help"
                           title="Panduan belum tersedia"
                           message="Belum ada panduan khusus untuk peran Anda." />
        @endforelse

        <section class="phm-card">
            <h2 class="phm-card-heading">Butuh bantuan lain?</h2>
            <p class="phm-helper mt-2">
                Tekan tombol biru Beesha di sudut kanan bawah untuk bertanya langsung,
                atau hubungi atasan Anda bila masalahnya terkait data pekerjaan.
            </p>
        </section>
    </div>
</x-app-layout>
