<x-app-layout title="Audit Aktivitas" page-title="Audit Aktivitas">
    <div class="space-y-5">
        <x-page-header title="Audit Aktivitas"
                       subtitle="Catatan siapa melakukan apa, termasuk tindakan yang tidak muncul di aktivitas umum." />

        {{-- Action filter --}}
        <div class="phm-card">
            <p class="phm-label">Filter berdasarkan tindakan</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('system.audit') }}"
                   @class([
                       'min-h-11 rounded-full border px-4 py-2 text-sm font-medium transition-colors',
                       'border-brand bg-brand text-white' => ! $action,
                       'border-line-strong bg-surface text-body hover:border-brand' => (bool) $action,
                   ])>
                    Semua
                </a>
                @foreach ($actions as $row)
                    <a href="{{ route('system.audit', ['aksi' => $row->action]) }}"
                       @class([
                           'min-h-11 rounded-full border px-4 py-2 text-sm font-medium transition-colors',
                           'border-brand bg-brand text-white' => $action === $row->action,
                           'border-line-strong bg-surface text-body hover:border-brand' => $action !== $row->action,
                       ])>
                        {{ $row->action }}
                        <span class="opacity-70">({{ $row->jumlah }})</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="phm-card">
            @forelse ($events as $event)
                <div class="flex items-start gap-3 border-b border-line py-3 first:pt-0 last:border-0 last:pb-0">
                    <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-brand/10 text-interactive">
                        <x-lucide-activity class="size-4" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-base leading-snug">{{ $event->summary }}</p>
                        <p class="phm-helper mt-0.5">
                            {{ $event->created_at->translatedFormat('d M Y · H:i:s') }} ·
                            <code class="rounded bg-surface-muted px-1.5 py-0.5 text-[0.75rem]">{{ $event->action }}</code>
                            @if ($event->entity_type)
                                · {{ $event->entity_type }}@if ($event->entity_id)#{{ $event->entity_id }}@endif
                            @endif
                            @if ($event->ip_address)
                                · {{ $event->ip_address }}
                            @endif
                        </p>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-base text-muted">Belum ada kejadian audit.</p>
            @endforelse
        </div>

        @if ($events->hasPages())
            <div class="phm-card py-4">{{ $events->links() }}</div>
        @endif
    </div>
</x-app-layout>
