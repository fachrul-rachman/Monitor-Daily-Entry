<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <title>{{ config('app.name', 'Dayta') }}</title>
    </head>
    <body class="min-h-screen bg-app-bg flex items-center justify-center px-6">
        <div class="w-full max-w-xs flex flex-col items-center text-center">
            {{-- Logo --}}
            <x-app-logo-icon class="h-14 w-auto mb-6" />

            {{-- App name --}}
            <h1 class="text-2xl font-bold text-text" style="font-family: 'DM Sans', sans-serif;">
                Dayta
            </h1>

            {{-- Description --}}
            <p class="text-base text-muted mt-2">
                Monitor aktivitas harian tim Anda.
            </p>

            {{-- CTA --}}
            <a
                href="{{ route('login') }}"
                class="btn-primary w-full mt-8"
                style="min-height: 48px; font-size: 16px;"
            >
                Masuk
            </a>
        </div>
    </body>
</html>
