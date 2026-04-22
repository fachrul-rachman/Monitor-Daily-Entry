<div>
    {{-- Page Header --}}
    <x-ui.page-header title="Home" :description="$todayDate">
        <x-slot:actions>
            @if($hasExceptions)
                <span class="inline-flex items-center gap-1.5 text-sm text-danger font-medium">
                    <span class="w-2 h-2 rounded-full bg-danger"></span>
                    Ada {{ $summaryCards['exceptions_today'] }} exception hari ini
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 text-sm text-success font-medium">
                    <span class="w-2 h-2 rounded-full bg-success"></span>
                    Tidak ada exception hari ini
                </span>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Status lines --}}
    <div class="flex flex-wrap gap-3 mb-6 -mt-2">
        @if($hasPendingLeave)
            <span class="text-sm text-warning font-medium">Ada {{ $summaryCards['pending_leave'] }} pending leave</span>
        @else
            <span class="text-sm text-success font-medium">Tidak ada pending leave</span>
        @endif
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-ui.summary-card
            label="Total Exception Hari Ini"
            :value="$summaryCards['exceptions_today']"
            context="Hari ini"
            :border="$summaryCards['exceptions_today'] > 0 ? 'danger' : null"
        />
        <x-ui.summary-card
            label="Pending Leave"
            :value="$summaryCards['pending_leave']"
            context="Menunggu persetujuan"
            :border="$summaryCards['pending_leave'] > 0 ? 'warning' : null"
        />
        <x-ui.summary-card
            label="Notifikasi Gagal"
            :value="$summaryCards['failed_notifications']"
            context="7 hari terakhir"
            :border="$summaryCards['failed_notifications'] > 0 ? 'danger' : null"
        />
        <x-ui.summary-card
            label="User Aktif"
            :value="$summaryCards['active_users']"
            context="Total terdaftar"
        />
    </div>

    {{-- Shortcut Actions --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
        <a href="{{ route('admin.users') }}" class="btn-secondary text-left gap-3 justify-start">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Tambah User
        </a>
        <a href="{{ route('admin.leave') }}" class="btn-secondary text-left gap-3 justify-start">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Setujui Leave
        </a>
        <a href="{{ route('admin.notifications') }}" class="btn-secondary text-left gap-3 justify-start">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Riwayat Notifikasi
        </a>
        <a href="{{ route('admin.override') }}" class="btn-secondary text-left gap-3 justify-start">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536M9 11l6.232-6.232a2.5 2.5 0 113.536 3.536L12.5 14.572V18h3.428l6.232-6.232a2.5 2.5 0 00-3.536-3.536L15.232 5.232M9 11H6.5A2.5 2.5 0 004 13.5V19a1 1 0 001 1h5.5A2.5 2.5 0 0013 17.5V15"/></svg>
            Override Entry
        </a>
    </div>

    {{-- Lists --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-ui.card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-text">Pending Leave</h3>
                <a href="{{ route('admin.leave') }}" class="text-sm text-primary font-medium hover:underline">Lihat semua</a>
            </div>

            @forelse($pendingLeaveList as $r)
                <div class="py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-medium text-text">{{ $r['name'] }}</p>
                            <p class="text-sm text-muted mt-0.5">{{ $r['division'] }} · {{ $r['type'] }}</p>
                            <p class="text-sm text-muted mt-0.5">{{ $r['date'] }}</p>
                        </div>
                        <x-ui.status-badge :status="$r['status']" />
                    </div>
                </div>
            @empty
                <x-ui.empty-state icon="calendar" title="Tidak ada permintaan leave yang pending" description="Jika ada pengajuan baru, akan muncul di sini." class="py-8" />
            @endforelse
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-text">Notifikasi Gagal</h3>
                <a href="{{ route('admin.notifications') }}" class="text-sm text-primary font-medium hover:underline">Lihat semua</a>
            </div>

            @forelse($failedNotifications as $n)
                <div class="py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-text truncate">{{ ucfirst($n['channel']) }} · {{ $n['time'] }}</p>
                            <p class="text-sm text-muted mt-1 truncate">{{ $n['error'] }}</p>
                        </div>
                        <x-ui.status-badge status="failed" />
                    </div>
                </div>
            @empty
                <x-ui.empty-state icon="bell" title="Tidak ada notifikasi gagal" description="Jika ada notifikasi yang gagal, akan muncul di sini." class="py-8" />
            @endforelse
        </x-ui.card>
    </div>
</div>

