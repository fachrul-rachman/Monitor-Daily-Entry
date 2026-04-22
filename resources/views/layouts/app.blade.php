<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-app-bg" x-data="{ sidebarOpen: false }">

    {{-- ============================================
         SIDEBAR — Desktop: fixed, Mobile: drawer
         ============================================ --}}
    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/50 md:hidden"
    ></div>

    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-40 w-64 bg-sidebar-bg transform transition-transform duration-200 ease-out md:translate-x-0 md:static md:z-auto flex flex-col"
    >
        {{-- Logo / Brand --}}
        <div class="flex items-center gap-3 px-5 h-16 border-b border-white/10 shrink-0">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
                <span class="text-white text-sm font-bold">D</span>
            </div>
            <span class="text-white font-semibold text-base" style="font-family: 'DM Sans', sans-serif;">Dayta</span>
            {{-- Close button mobile --}}
            <button @click="sidebarOpen = false" class="ml-auto text-sidebar-text hover:text-white md:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4">
            {{-- TODO: Ganti navigasi berdasarkan auth()->user()->role --}}
            {{-- Dummy nav — Admin --}}
            <p class="px-4 py-2 text-sm uppercase tracking-widest text-sidebar-section">Menu Utama</p>

            <a href="#" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm bg-sidebar-active-bg text-sidebar-active-text">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                Home
            </a>

            <a href="#" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm text-sidebar-text hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                Users
            </a>

            <a href="#" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm text-sidebar-text hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Divisi
            </a>

            <p class="px-4 py-2 mt-4 text-sm uppercase tracking-widest text-sidebar-section">Kelola</p>

            <a href="#" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm text-sidebar-text hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Assignment
            </a>

            <a href="#" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm text-sidebar-text hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Pengaturan
            </a>

            <a href="#" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm text-sidebar-text hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Cuti & Izin
            </a>

            <a href="#" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm text-sidebar-text hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Override
            </a>

            <a href="#" class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm text-sidebar-text hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                Riwayat Notifikasi
            </a>
        </nav>

        {{-- User area --}}
        <div class="border-t border-white/10 p-4 shrink-0">
            {{-- TODO: Ganti dengan data auth()->user() --}}
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-xs text-white font-bold shrink-0">
                    AD
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">Admin User</p>
                    <p class="text-sm text-sidebar-text truncate">Admin · Sistem</p>
                </div>
            </div>
            {{-- TODO: Ganti dengan form POST logout --}}
            <button class="mt-3 text-sm text-sidebar-section hover:text-white transition-colors cursor-pointer">
                Logout
            </button>
        </div>
    </aside>

    {{-- ============================================
         MAIN CONTENT
         ============================================ --}}
    <div class="md:ml-64 min-h-screen flex flex-col">
        {{-- Mobile topbar --}}
        <header class="sticky top-0 z-20 bg-surface border-b border-border h-14 flex items-center px-4 gap-3 md:hidden">
            <button @click="sidebarOpen = true" class="text-text hover:text-primary cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-base font-semibold text-text truncate" style="font-family: 'DM Sans', sans-serif;">
                {{-- TODO: Bind ke $title dari halaman --}}
                {{ $title ?? 'Dayta' }}
            </h1>
        </header>

        {{-- Page content --}}
        <main class="flex-1 px-4 py-6 md:px-8 md:py-8">
            {{ $slot }}
        </main>
    </div>

    {{-- ============================================
         TOAST CONTAINER
         ============================================ --}}
    <x-ui.toast />

    @livewireScripts
</body>
</html>
