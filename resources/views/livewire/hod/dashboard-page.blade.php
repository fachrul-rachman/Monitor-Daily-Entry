{{--
    HoD Dashboard Page
    Route: /hod/dashboard
    Component: App\Livewire\Hod\DashboardPage
--}}

<x-layouts.app title="Dashboard HoD">
    @php
        $todayDate = 'Senin, 7 Juli 2025';
        $summaryCards = [
            'plan_today' => 2,
            'realization_pending' => 2,
            'manager_findings' => 4,
            'stagnant_roadmap' => 1,
        ];
        $planFilled = false; // dummy: plan belum diisi
        $realizationFilled = false;
        $activeBigRocks = [
            ['title' => 'Optimasi Proses Operasional Q3', 'roadmap_count' => 4, 'progress' => 60, 'status' => 'on_track'],
            ['title' => 'Pengembangan SDM Tim', 'roadmap_count' => 3, 'progress' => 35, 'status' => 'at_risk'],
        ];
        $managersNeedingAttention = [
            ['name' => 'Budi Santoso', 'findings' => 4, 'severity' => 'major', 'latest' => 'Missing plan 3 hari'],
            ['name' => 'Rudi Hermawan', 'findings' => 2, 'severity' => 'medium', 'latest' => 'Late submission berulang'],
            ['name' => 'Eko Prasetyo', 'findings' => 1, 'severity' => 'minor', 'latest' => 'Format tidak sesuai'],
        ];
    @endphp

    <x-ui.page-header title="Dashboard" :description="$todayDate" />

    {{-- Summary Cards 2x2 --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        <x-ui.summary-card label="Plan Hari Ini" :value="$summaryCards['plan_today']" context="Item direncanakan" />
        <x-ui.summary-card label="Realisasi Pending" :value="$summaryCards['realization_pending']" context="Belum diisi" :border="$summaryCards['realization_pending'] > 0 ? 'warning' : null" />
        <x-ui.summary-card label="Temuan Manager" :value="$summaryCards['manager_findings']" context="Minggu ini" :border="$summaryCards['manager_findings'] > 0 ? 'danger' : null" />
        <x-ui.summary-card label="Roadmap Stagnant" :value="$summaryCards['stagnant_roadmap']" context="Perlu tindakan" :border="$summaryCards['stagnant_roadmap'] > 0 ? 'warning' : null" />
    </div>

    {{-- CTA Section --}}
    @if(!$planFilled)
        <div class="bg-primary-light border border-primary/30 rounded-xl p-5 mb-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-primary">Plan Hari Ini Belum Diisi</p>
                        <p class="text-xs text-primary/70">Window Plan: 08:00 – 17:00</p>
                    </div>
                </div>
                {{-- TODO: href ke daily-entry --}}
                <a href="#" class="btn-primary shrink-0">Isi Plan Sekarang</a>
            </div>
        </div>
    @elseif(!$realizationFilled)
        <div class="bg-warning-bg border border-warning/30 rounded-xl p-5 mb-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-warning/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-warning">Realisasi Belum Diisi</p>
                        <p class="text-xs text-warning/70">Window Realisasi: 15:00 – 23:59</p>
                    </div>
                </div>
                <a href="#" class="btn-primary shrink-0">Isi Realisasi</a>
            </div>
        </div>
    @endif

    {{-- Active Big Rocks --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-text mb-3">Big Rock Aktif</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($activeBigRocks as $br)
                <x-ui.card>
                    <div class="flex items-start justify-between">
                        <p class="font-semibold text-text text-sm">{{ $br['title'] }}</p>
                        <x-ui.status-badge :status="$br['status']" />
                    </div>
                    <p class="text-xs text-muted mt-1">{{ $br['roadmap_count'] }} roadmap items</p>
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs text-muted mb-1">
                            <span>Progress</span>
                            <span>{{ $br['progress'] }}%</span>
                        </div>
                        <div class="w-full h-2 bg-app-bg rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all" style="width: {{ $br['progress'] }}%"></div>
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>

    {{-- Managers Needing Attention --}}
    <div class="mb-6">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Manager Perlu Perhatian</h3>
            @foreach($managersNeedingAttention as $mgr)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-text">{{ $mgr['name'] }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ $mgr['latest'] }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui.severity-badge :severity="$mgr['severity']" />
                        <span class="text-xs text-muted">{{ $mgr['findings'] }}x</span>
                    </div>
                </div>
            @endforeach
        </x-ui.card>
    </div>

    {{-- Quick Actions --}}
    <div class="flex flex-wrap gap-3">
        <a href="#" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Riwayat Entry
        </a>
        <a href="#" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Big Rock
        </a>
        <a href="#" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Lihat Entry Divisi
        </a>
        <a href="#" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            AI Chat
        </a>
    </div>
</x-layouts.app>
