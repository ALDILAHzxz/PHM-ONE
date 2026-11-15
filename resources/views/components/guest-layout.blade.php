@props(['title' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — PHM One' : 'PHM One' }}</title>

    <script>
        (function () {
            try {
                var stored = localStorage.getItem('phm-theme');
                var dark = stored === 'dark' || (stored === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>

    <meta name="theme-color" content="#1B365D" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0F1B2D" media="(prefers-color-scheme: dark)">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-page text-body antialiased">

<div class="flex min-h-dvh flex-col">
    {{-- Minimal header: brand on the left, theme toggle on the right. --}}
    <header class="bg-navbar">
        <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-4 md:h-18 md:px-6">
            <div class="flex items-center gap-2.5">
                <span class="grid size-9 place-items-center rounded-[0.625rem] bg-white/15 font-bold text-white">P1</span>
                <div>
                    <p class="text-[1.0625rem] leading-tight font-semibold text-white">PHM One</p>
                    <p class="text-[0.75rem] leading-tight text-white/70">PT Putra Hatim Mandiri</p>
                </div>
            </div>

            <x-theme-toggle />
        </div>
    </header>

    <main class="flex flex-1 items-start justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-[26rem]">
            {{ $slot }}
        </div>
    </main>

    <footer class="px-4 py-6 text-center">
        <p class="phm-helper">
            PHM One — Powered by Beesha
            @if (config('phm.demo_mode'))
                <span class="mx-1">·</span>
                <span class="font-semibold text-amber-700 dark:text-amber-300">Mode Demo</span>
            @endif
        </p>
    </footer>
</div>

</body>
</html>
