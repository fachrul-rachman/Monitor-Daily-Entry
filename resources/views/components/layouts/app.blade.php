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
        class="fixed inset-y-0 left-0 z-40 w-64 bg-sidebar-bg transform transition-transform duration-200 ease-out md:translate-x-0 flex flex-col"
    >
        {{-- Logo / Brand --}}
        <div class="flex items-center gap-3 px-5 h-16 border-b border-white/10 shrink-0">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
                    <span class="text-white text-sm font-bold">D</span>
                </div>
                <span class="text-white font-semibold text-base" style="font-family: 'DM Sans', sans-serif;">Dayta</span>
            </a>
            {{-- Close button mobile --}}
            <button @click="sidebarOpen = false" class="ml-auto text-sidebar-text hover:text-white md:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation — Dynamic per role based on URL prefix --}}
        @php
            $currentPath = request()->path();
            $currentRole = explode('/', $currentPath)[0] ?? '';
            $currentRoute = request()->route()?->getName() ?? '';
        @endphp

        <nav class="flex-1 overflow-y-auto py-4">
            @if($currentRole === 'admin')
                {{-- ===== ADMIN SIDEBAR ===== --}}
                <p class="px-4 py-2 text-sm uppercase tracking-widest text-sidebar-section">Menu Utama</p>

                <a href="{{ route('admin.home') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'admin.home' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                    Home
                </a>
                <a href="{{ route('admin.users') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'admin.users' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                    Users
                </a>
                <a href="{{ route('admin.divisions') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'admin.divisions' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Divisi
                </a>

                <p class="px-4 py-2 mt-4 text-sm uppercase tracking-widest text-sidebar-section">Kelola</p>

                <a href="{{ route('admin.assignments') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'admin.assignments' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Assignment
                </a>
                <a href="{{ route('admin.settings') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'admin.settings' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Pengaturan
                </a>
                <a href="{{ route('admin.leave') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'admin.leave' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Cuti & Izin
                </a>
                <a href="{{ route('admin.override') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'admin.override' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Override
                </a>
                <a href="{{ route('admin.notifications') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'admin.notifications' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Riwayat Notifikasi
                </a>

            @elseif($currentRole === 'director')
                {{-- ===== DIRECTOR SIDEBAR ===== --}}
                <p class="px-4 py-2 text-sm uppercase tracking-widest text-sidebar-section">Analisis</p>

                <a href="{{ route('director.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'director.dashboard' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('director.company') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'director.company' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Company
                </a>
                <a href="{{ route('director.divisions') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'director.divisions' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Divisi
                </a>

                <p class="px-4 py-2 mt-4 text-sm uppercase tracking-widest text-sidebar-section">Kelola</p>

                {{-- Director punya akses administrasi terbatas seperti Admin --}}
                <a href="{{ route('director.manage.users') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'director.manage.users' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                    Users
                </a>
                <a href="{{ route('director.manage.divisions') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'director.manage.divisions' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Divisi
                </a>
                <a href="{{ route('director.manage.assignments') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'director.manage.assignments' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Assignment HoD
                </a>
                <a href="{{ route('director.manage.leave') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'director.manage.leave' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Cuti & Izin
                </a>

                <p class="px-4 py-2 mt-4 text-sm uppercase tracking-widest text-sidebar-section">Tools</p>

                <a href="{{ route('director.ai-chat') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'director.ai-chat' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                    AI Chat
                </a>

            @elseif($currentRole === 'hod')
                {{-- ===== HOD SIDEBAR ===== --}}
                <p class="px-4 py-2 text-sm uppercase tracking-widest text-sidebar-section">Personal</p>

                <a href="{{ route('hod.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.dashboard' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('hod.daily-entry') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.daily-entry' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Entry Harian
                </a>
                <a href="{{ route('hod.history') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.history' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Riwayat
                </a>
                <a href="{{ route('hod.big-rock') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.big-rock' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Big Rock
                </a>

                <p class="px-4 py-2 mt-4 text-sm uppercase tracking-widest text-sidebar-section">Divisi</p>

	                <a href="{{ route('hod.division-entries') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.division-entries' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
	                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
	                    Entry Divisi
	                </a>
	                <a href="{{ route('hod.team-big-rock') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.team-big-rock' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
	                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.5c-1.657 0-3-1.12-3-2.5S10.343 1.5 12 1.5s3 1.12 3 2.5-1.343 2.5-3 2.5zM4.5 20.5v-1.2c0-2.4 3.2-4.3 7.5-4.3s7.5 1.9 7.5 4.3v1.2H4.5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 12.5c-.9-.5-1.5-1.2-1.5-2 0-1.5 2.7-2.8 6-2.8s6 1.3 6 2.8c0 .8-.6 1.5-1.5 2"/></svg>
	                    Big Rock Tim
	                </a>
	                <a href="{{ route('hod.division-summary') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.division-summary' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
	                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
	                    Ringkasan Divisi
	                </a>
	                <a href="{{ route('hod.leave') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.leave' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
	                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
	                    Cuti & Izin
	                </a>

	                <p class="px-4 py-2 mt-4 text-sm uppercase tracking-widest text-sidebar-section">Tools</p>

                <a href="{{ route('hod.ai-chat') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'hod.ai-chat' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                    AI Chat
                </a>

            @elseif($currentRole === 'manager')
                {{-- ===== MANAGER SIDEBAR ===== --}}
                <p class="px-4 py-2 text-sm uppercase tracking-widest text-sidebar-section">Menu Utama</p>

                <a href="{{ route('manager.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'manager.dashboard' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('manager.daily-entry') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'manager.daily-entry' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Entry Harian
                </a>
                <a href="{{ route('manager.history') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'manager.history' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Riwayat
                </a>
                <a href="{{ route('manager.big-rock') }}" class="flex items-center gap-2.5 px-4 py-2 mx-2 rounded-lg text-sm {{ $currentRoute === 'manager.big-rock' ? 'bg-sidebar-active-bg text-sidebar-active-text' : 'text-sidebar-text hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Big Rock
                </a>
            @endif
        </nav>

        {{-- User area --}}
        <div class="border-t border-white/10 p-4 shrink-0" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false" @keydown.escape.window="userMenuOpen = false">
            @php
                $roleLabels = ['admin' => 'Admin', 'director' => 'Director', 'hod' => 'Head of Division', 'manager' => 'Manager'];
                $roleNames = ['admin' => 'Admin User', 'director' => 'Direktur Utama', 'hod' => 'Budi Hartono', 'manager' => 'Rudi Santoso'];
                $roleInitials = ['admin' => 'AD', 'director' => 'DU', 'hod' => 'BH', 'manager' => 'RS'];
                $roleDivisions = ['admin' => 'Sistem', 'director' => 'Perusahaan', 'hod' => 'Divisi Operasional', 'manager' => 'Divisi Operasional'];
            @endphp
            @if(auth()->check())
                <button type="button" @click="userMenuOpen = !userMenuOpen" class="w-full flex items-center gap-3 text-left rounded-lg hover:bg-white/5 transition-colors p-2 -m-2">
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-xs text-white font-bold shrink-0">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-sidebar-text truncate">
                            {{ $roleLabels[auth()->user()->role] ?? 'User' }}{{ auth()->user()->division?->name ? ' · '.auth()->user()->division->name : '' }}
                        </p>
                    </div>
                    <svg class="w-4 h-4 text-sidebar-text shrink-0 transition-transform" :class="userMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="userMenuOpen"
                    x-transition:enter="transition ease-out duration-120"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="mt-3 rounded-lg overflow-hidden border border-white/10 bg-white/5"
                    style="display: none;"
                >
                    <a href="{{ route(auth()->user()->role.'.security') }}" class="block px-3 py-2 text-sm text-sidebar-text hover:text-white hover:bg-white/5 transition-colors">
                        Ganti Password
                    </a>
                    <div class="h-px bg-white/10"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 text-sm text-danger hover:bg-danger-bg transition-colors cursor-pointer">
                            Logout
                        </button>
                    </form>
                </div>
            @else
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-xs text-white font-bold shrink-0">
                    {{ $roleInitials[$currentRole] ?? 'U' }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ $roleNames[$currentRole] ?? 'User' }}</p>
                    <p class="text-sm text-sidebar-text truncate">{{ $roleLabels[$currentRole] ?? 'Unknown' }} · {{ $roleDivisions[$currentRole] ?? '' }}</p>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <a href="{{ url('/') }}" class="text-sm text-sidebar-section hover:text-white transition-colors">← Ganti Role</a>
                <span class="text-sm text-sidebar-section">Preview Mode</span>
            </div>
            @endif
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
