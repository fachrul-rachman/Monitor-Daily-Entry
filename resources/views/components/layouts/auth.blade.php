<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-app-bg">

    {{-- ============================================
         AUTH LAYOUT — Desktop: split, Mobile: centered card
         ============================================ --}}
    <div class="min-h-screen flex">

        {{-- Left branding panel — hidden on mobile --}}
        <div class="hidden md:flex md:w-2/5 bg-primary flex-col items-center justify-center px-10 relative overflow-hidden">
            {{-- Subtle pattern overlay --}}
            <div class="absolute inset-0 opacity-5">
                <div class="absolute top-10 left-10 w-32 h-32 rounded-full border-2 border-white"></div>
                <div class="absolute bottom-20 right-10 w-48 h-48 rounded-full border-2 border-white"></div>
                <div class="absolute top-1/3 right-1/4 w-20 h-20 rounded-full border-2 border-white"></div>
            </div>

            <div class="relative z-10 text-center">
                {{-- Logo --}}
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mx-auto mb-6">
                    <span class="text-white text-2xl font-bold" style="font-family: 'DM Sans', sans-serif;">D</span>
                </div>

                <h1 class="text-3xl font-bold text-white mb-3" style="font-family: 'DM Sans', sans-serif;">
                    Dayta
                </h1>
                <p class="text-white/70 text-sm leading-relaxed max-w-xs mx-auto">
                    Daily Execution Monitoring System — Pantau, evaluasi, dan kelola eksekusi harian tim Anda.
                </p>
            </div>
        </div>

        {{-- Right form panel --}}
        <div class="flex-1 flex items-center justify-center px-4 py-8 md:px-10">
            <div class="w-full max-w-sm">
                {{-- Mobile logo (hidden on desktop) --}}
                <div class="flex flex-col items-center mb-8 md:hidden">
                    <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center mb-3">
                        <span class="text-white text-lg font-bold" style="font-family: 'DM Sans', sans-serif;">D</span>
                    </div>
                    <h1 class="text-xl font-bold text-text" style="font-family: 'DM Sans', sans-serif;">Dayta</h1>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
