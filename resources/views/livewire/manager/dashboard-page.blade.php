{{--
    Manager Dashboard Page
    Route: /manager/dashboard
    Component: App\Livewire\Manager\DashboardPage
    Lebih simpel dari HoD — tidak ada section divisi. Satu fokus: isi harian.
--}}

<x-layouts.app title="Dashboard">
    @php
        $todayDate = 'Senin, 7 Juli 2025';
        $summaryCards = [
            'plan_today' => ['value' => 2, 'label' => 'Sudah diisi'],
            'realization_today' => ['value' => 0, 'label' => 'Belum diisi'],
            'big_rock_active' => ['value' => 2, 'label' => 'Aktif'],
            'recent_findings' => ['value' => 1, 'label' => 'Minggu ini'],
        ];
        $planFilled = true;
        $realizationFilled = false;

        $activeRoadmapItems = [
            ['title' => 'Review dokumen procurement', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Implementasi SOP', 'status' => 'in_progress'],
            ['title' => 'Training modul baru', 'big_rock' => 'Pengembangan SDM', 'roadmap' => 'Training Tim', 'status' => 'planned'],
            ['title' => 'Evaluasi vendor Q3', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Audit Proses', 'status' => 'not_started'],
        ];

        $recentHistory = [
            ['title' => 'Review dokumen procurement', 'date' => '7 Jul', 'plan' => 'submitted', 'real' => 'missing'],
            ['title' => 'Koordinasi tim lapangan', 'date' => '7 Jul', 'plan' => 'submitted', 'real' => 'finished'],
            ['title' => 'Meeting komite SOP', 'date' => '4 Jul', 'plan' => 'submitted', 'real' => 'in_progress'],
            ['title' => 'Finalisasi budget Q3', 'date' => '3 Jul', 'plan' => 'late', 'real' => 'finished'],
            ['title' => 'Review performa tim', 'date' => '3 Jul', 'plan' => 'submitted', 'real' => 'not_finished'],
        ];
    @endphp

    <x-ui.page-header title="Dashboard" :description="$todayDate" />

    {{-- Summary Cards 2x2 --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        <x-ui.summary-card
            label="Status Plan Hari Ini"
            :value="$summaryCards['plan_today']['value']"
            :context="$summaryCards['plan_today']['label']"
            :border="$planFilled ? 'success' : 'warning'"
        />
        <x-ui.summary-card
            label="Status Realisasi Hari Ini"
            :value="$summaryCards['realization_today']['value']"
            :context="$summaryCards['realization_today']['label']"
            :border="$realizationFilled ? 'success' : 'warning'"
        />
        <x-ui.summary-card
            label="Big Rock Aktif"
            :value="$summaryCards['big_rock_active']['value']"
            :context="$summaryCards['big_rock_active']['label']"
        />
        <x-ui.summary-card
            label="Temuan Terbaru"
            :value="$summaryCards['recent_findings']['value']"
            :context="$summaryCards['recent_findings']['label']"
            :border="$summaryCards['recent_findings']['value'] > 0 ? 'danger' : null"
        />
    </div>

    {{-- CTA utama — sangat prominent --}}
    @if(!$planFilled)
        <div class="bg-primary-light border-2 border-primary/40 rounded-xl p-6 mb-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-primary text-base">Plan Hari Ini Belum Diisi</p>
                        <p class="text-sm text-primary/70">Window Plan: 08:00 – 17:00. Segera isi plan harian Anda.</p>
                    </div>
                </div>
                {{-- TODO: href ke route('manager.daily-entry') --}}
                <a href="#" class="btn-primary shrink-0 text-base px-6 py-3">Isi Plan Sekarang</a>
            </div>
        </div>
    @elseif(!$realizationFilled)
        <div class="bg-warning-bg border-2 border-warning/40 rounded-xl p-6 mb-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-warning/20 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-warning text-base">Realisasi Belum Diisi</p>
                        <p class="text-sm text-warning/70">Window Realisasi: 15:00 – 23:59. Isi realisasi sebelum tutup.</p>
                    </div>
                </div>
                <a href="#" class="btn-primary shrink-0 text-base px-6 py-3">Isi Realisasi</a>
            </div>
        </div>
    @else
        <div class="bg-success-bg border border-success/30 rounded-xl p-5 mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-success shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium text-success">Selesai hari ini! Plan dan realisasi sudah terisi semua.</p>
        </div>
    @endif

    {{-- Active Roadmap Items --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-text mb-3">Roadmap Item Aktif</h3>
        <div class="space-y-2">
            @foreach($activeRoadmapItems as $item)
                <x-ui.card class="!py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text truncate">{{ $item['title'] }}</p>
                            <div class="flex flex-wrap items-center gap-1.5 text-xs mt-1">
                                <span class="badge-primary">{{ $item['big_rock'] }}</span>
                                <svg class="w-3 h-3 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <span class="badge-muted">{{ $item['roadmap'] }}</span>
                            </div>
                        </div>
                        <x-ui.status-badge :status="$item['status']" />
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>

    {{-- Recent History Timeline --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-text mb-3">Riwayat Terbaru</h3>
        <div class="space-y-2">
            @foreach($recentHistory as $entry)
                <x-ui.card class="!py-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text truncate">{{ $entry['title'] }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ $entry['date'] }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 text-xs">
                            <span class="text-muted">P:</span> <x-ui.status-badge :status="$entry['plan']" />
                            <span class="text-muted ml-1">R:</span> <x-ui.status-badge :status="$entry['real']" />
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
        {{-- TODO: href ke route('manager.history') --}}
        <a href="#" class="text-sm text-primary font-medium mt-3 inline-block hover:underline">Lihat Semua Riwayat →</a>
    </div>
</x-layouts.app>
