{{--
    Role Switcher — Landing page untuk preview UI
    Pilih role untuk explore semua halaman
    TODO: Hapus setelah auth backend siap
--}}

<x-layouts.auth title="Pilih Role">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-text" style="font-family: 'DM Sans', sans-serif;">
            Preview Mode
        </h2>
        <p class="text-sm text-muted mt-1">Pilih role untuk melihat semua halaman</p>
    </div>

    <div class="space-y-3">
        {{-- Admin --}}
        <a href="{{ route('admin.home') }}"
           class="flex items-center gap-4 p-4 bg-surface border border-border rounded-xl hover:border-primary/40 hover:shadow-md transition-all group">
            <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-text group-hover:text-primary transition-colors">Admin</p>
                <p class="text-sm text-muted">User, Divisi, Assignment, Setting, Cuti, Override, Notifikasi</p>
            </div>
            <svg class="w-5 h-5 text-muted group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        {{-- Director --}}
        <a href="{{ route('director.dashboard') }}"
           class="flex items-center gap-4 p-4 bg-surface border border-border rounded-xl hover:border-primary/40 hover:shadow-md transition-all group">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-text group-hover:text-primary transition-colors">Director</p>
                <p class="text-sm text-muted">Dashboard, Company, Divisions, AI Chat</p>
            </div>
            <svg class="w-5 h-5 text-muted group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        {{-- HoD --}}
        <a href="{{ route('hod.dashboard') }}"
           class="flex items-center gap-4 p-4 bg-surface border border-border rounded-xl hover:border-primary/40 hover:shadow-md transition-all group">
            <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-text group-hover:text-primary transition-colors">Head of Division</p>
                <p class="text-sm text-muted">Dashboard, Entry Harian, Big Rock, Divisi, AI Chat</p>
            </div>
            <svg class="w-5 h-5 text-muted group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        {{-- Manager --}}
        <a href="{{ route('manager.dashboard') }}"
           class="flex items-center gap-4 p-4 bg-surface border border-border rounded-xl hover:border-primary/40 hover:shadow-md transition-all group">
            <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-text group-hover:text-primary transition-colors">Manager</p>
                <p class="text-sm text-muted">Dashboard, Entry Harian, Riwayat, Big Rock</p>
            </div>
            <svg class="w-5 h-5 text-muted group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm text-primary font-medium hover:underline">Lihat Halaman Login →</a>
    </div>
</x-layouts.auth>
