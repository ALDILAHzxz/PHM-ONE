@props([
    'title' => null,
    'pageTitle' => null,
])

@php
    $user = auth()->user();
    $navItems = \App\Support\Navigation::for($user);
    $activeRole = $user->activeRole();
@endphp

<!DOCTYPE html>
<html lang="id" class="scroll-pt-20">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — PHM One' : 'PHM One' }}</title>

    {{-- Applied before first paint so the correct theme never flashes. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('phm-theme');
                var dark = stored === 'dark' || (stored === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {
                /* private mode / storage blocked — fall back to light theme */
            }
        })();
    </script>

    <meta name="theme-color" content="#1B365D" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0F1B2D" media="(prefers-color-scheme: dark)">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-page text-body antialiased">

<a href="#konten-utama"
   class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[90] focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2 focus:font-semibold focus:text-interactive focus:shadow-lg">
    Lewati ke konten utama
</a>

<div x-data="{ drawer: false }" @keydown.escape.window="drawer = false">

    {{-- ===================================================================
         GLOBAL NAVBAR — sticky, 64px mobile / 72px desktop.
         Action controls are ICON ONLY (no visible text labels beside them).
         Theme toggle and profile sit at the top-right corner.
    ==================================================================== --}}
    <header class="sticky top-0 z-50 bg-navbar shadow-[0_1px_0_rgba(255,255,255,0.08)]">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-3 sm:px-4 md:h-18 md:px-8">

            {{-- LEFT: hamburger + brand --}}
            <div class="flex min-w-0 items-center gap-2 md:gap-3">
                <button type="button"
                        class="phm-icon-btn"
                        aria-label="Buka menu navigasi"
                        title="Menu"
                        :aria-expanded="drawer.toString()"
                        aria-controls="drawer-navigasi"
                        @click="drawer = true">
                    <x-lucide-menu class="size-6" aria-hidden="true" />
                </button>

                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5 rounded-lg px-1 py-1">
                    <span class="grid size-9 shrink-0 place-items-center rounded-[0.625rem] bg-white/15 font-bold text-white">
                        P1
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-[1.0625rem] leading-tight font-semibold text-white">PHM One</span>
                        <span class="hidden truncate text-[0.75rem] leading-tight text-white/70 sm:block">
                            {{ $pageTitle ?? 'Powered by Beesha' }}
                        </span>
                    </span>
                </a>
            </div>

            {{-- RIGHT: theme toggle + profile, icon only --}}
            <div class="flex items-center gap-2 md:gap-3">
                <x-theme-toggle />

                <a href="{{ route('profile') }}"
                   class="phm-icon-btn"
                   aria-label="Profil dan pengaturan akun"
                   title="Profil">
                    <x-lucide-circle-user class="size-6" aria-hidden="true" />
                </a>
            </div>
        </div>
    </header>

    {{-- ===================================================================
         HAMBURGER DRAWER — role-filtered routes with icon + readable label.
    ==================================================================== --}}
    <div x-cloak x-show="drawer" class="relative z-[60]" role="dialog" aria-modal="true" aria-label="Menu navigasi">
        <div x-show="drawer"
             x-transition.opacity.duration.200ms
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-[2px]"
             @click="drawer = false"
             aria-hidden="true"></div>

        <aside id="drawer-navigasi"
               x-show="drawer"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               x-trap.noscroll="drawer"
               class="fixed inset-y-0 left-0 flex w-[88vw] max-w-[320px] flex-col bg-surface shadow-2xl">

            <div class="flex items-center justify-between border-b border-line px-4 py-4">
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold">{{ $user->full_name }}</p>
                    <p class="truncate text-sm text-muted">{{ $activeRole?->name ?? 'Tanpa peran' }}</p>
                </div>
                <button type="button"
                        class="grid size-11 place-items-center rounded-[0.6875rem] text-muted transition-colors hover:bg-brand/10 hover:text-interactive"
                        aria-label="Tutup menu navigasi"
                        @click="drawer = false">
                    <x-lucide-x class="size-6" aria-hidden="true" />
                </button>
            </div>

            <nav class="phm-scroll flex-1 space-y-1 overflow-y-auto p-3" aria-label="Menu utama">
                @foreach ($navItems as $item)
                    @php $isActive = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       @click="drawer = false"
                       @class(['phm-drawer-link', 'phm-drawer-link-active' => $isActive])
                       @if ($isActive) aria-current="page" @endif>
                        <x-dynamic-component :component="'lucide-' . $item['icon']"
                                             class="size-5 shrink-0 text-icon-accent"
                                             aria-hidden="true" />
                        <span class="truncate">{{ $item['label'] }}</span>
                        @if ($isActive)
                            <x-lucide-chevron-right class="ml-auto size-4 shrink-0" aria-hidden="true" />
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-line p-3">
                <form method="POST" action="{{ route('logout') }}"
                      onsubmit="return confirm('Keluar dari PHM One sekarang?')">
                    @csrf
                    <button type="submit" class="phm-drawer-link w-full text-left hover:bg-rose-500/10 hover:text-rose-600 dark:hover:text-rose-300">
                        <x-lucide-log-out class="size-5 shrink-0" aria-hidden="true" />
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>
    </div>

    {{-- ===================================================================
         PAGE CONTENT
         pb-28 keeps the floating Beesha button clear of page actions.
    ==================================================================== --}}
    <main id="konten-utama" class="mx-auto w-full max-w-7xl px-4 pt-5 pb-28 sm:px-6 md:pt-8 md:pb-32">
        @if (config('phm.demo_mode'))
            <x-demo-banner />
        @endif

        <x-flash-messages />

        {{ $slot }}
    </main>

    {{-- ===================================================================
         GLOBAL FLOATING BEESHA AI — present on every authenticated page.
    ==================================================================== --}}
    @if ($user->canUseBeesha())
        @livewire('beesha-assistant')
    @endif
</div>

</body>
</html>
