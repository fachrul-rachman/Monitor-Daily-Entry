{{--
    Admin Home Page
    Route: /admin
    Component: App\Livewire\Admin\HomePage
    Layout: layouts/app
--}}

<x-layouts.app title="Home">
    {{-- TODO: Bind semua data dari Livewire properties --}}
    @php
        // Dummy data
        $todayDate = 'Senin, 7 Juli 2025';
        $hasExceptions = true;
        $hasPendingLeave = true;
        $summaryCards = [
            'exceptions_today' => 3,
            'pending_leave' => 2,
            'failed_notifications' => 1,
            'active_users' => 48,
        ];
        $pendingLeaveList = [
            ['name' => 'Budi Santoso', 'division' => 'Operasional', 'type' => 'Cuti Tahunan', 'date' => '8-10 Jul 2025', 'status' => 'pending'],
            ['name' => 'Siti Rahayu', 'division' => 'Keuangan', 'type' => 'Izin Sakit', 'date' => '7 Jul 2025', 'status' => 'pending'],
            ['name' => 'Ahmad Fauzi', 'division' => 'IT', 'type' => 'Cuti Tahunan', 'date' => '9-11 Jul 2025', 'status' => 'pending'],
        ];
        $failedNotifications = [
            ['time' => '07:15', 'channel' => 'Email', 'error' => 'SMTP connection timeout'],
            ['time' => '06:30', 'channel' => 'WhatsApp', 'error' => 'API rate limit exceeded'],
        ];
    @endphp

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
            context="Perlu dicek"
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
        {{-- TODO: Hubungkan ke wire:click atau href sesuai route --}}
        <button class="btn-secondary text-left gap-3 justify-start">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Tambah User
        </button>
        <a href="#" class="btn-secondary text-left gap-3 justify-start">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Setujui Leave
        </a>
        <a href="#" class="btn-secondary text-left gap-3 justify-start">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Riwayat Notifikasi
        </a>
        <a href="#" class="btn-secondary text-left gap-3 justify-start">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Override Entry
        </a>
    </div>

    {{-- Two-column section: Pending Leave + Failed Notifications --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Pending Leave List --}}
        <x-ui.card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-text">Pending Leave</h3>
                {{-- TODO: href ke leave page --}}
                <a href="#" class="text-xs text-primary font-medium hover:underline">Lihat semua →</a>
            </div>

            @if(count($pendingLeaveList) > 0)
                <div class="space-y-3">
                    @foreach($pendingLeaveList as $leave)
                        <div class="flex items-start justify-between py-2 {{ !$loop->last ? 'border-b border-border' : '' }}">
                            <div>
                                <p class="text-sm font-medium text-text">{{ $leave['name'] }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ $leave['division'] }} · {{ $leave['type'] }}</p>
                                <p class="text-xs text-muted">{{ $leave['date'] }}</p>
                            </div>
                            <x-ui.status-badge :status="$leave['status']" />
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state
                    icon="calendar"
                    title="Tidak ada permintaan leave yang pending"
                    class="py-8"
                />
            @endif
        </x-ui.card>

        {{-- Failed Notifications --}}
        <x-ui.card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-text">Notifikasi Gagal</h3>
                {{-- TODO: href ke notification history page --}}
                <a href="#" class="text-xs text-primary font-medium hover:underline">Lihat semua →</a>
            </div>

            @if(count($failedNotifications) > 0)
                <div class="space-y-3">
                    @foreach($failedNotifications as $notif)
                        <div class="flex items-start justify-between py-2 {{ !$loop->last ? 'border-b border-border' : '' }}">
                            <div>
                                <p class="text-sm font-medium text-text">{{ $notif['channel'] }}</p>
                                <p class="text-xs text-danger mt-0.5">{{ $notif['error'] }}</p>
                            </div>
                            <span class="text-xs text-muted shrink-0">{{ $notif['time'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state
                    icon="bell"
                    title="Tidak ada notifikasi gagal"
                    class="py-8"
                />
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
