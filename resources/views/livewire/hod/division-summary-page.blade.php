{{--
    HoD Division Summary Page
    Route: /hod/division-summary
    Component: App\Livewire\Hod\DivisionSummaryPage
--}}

<x-layouts.app title="Ringkasan Divisi">
    @php
        $summaryCards = [
            'health_score' => 64,
            'total_entries_today' => 12,
            'on_time_rate' => '78%',
            'findings_this_week' => 6,
        ];
        $hc = $summaryCards['health_score'] >= 70 ? 'success' : ($summaryCards['health_score'] >= 40 ? 'warning' : 'danger');
        $managersAttention = [
            ['name' => 'Budi Santoso', 'findings' => 4, 'severity' => 'major', 'detail' => 'Missing 3x, Late 1x'],
            ['name' => 'Rudi Hermawan', 'findings' => 2, 'severity' => 'medium', 'detail' => 'Late submission berulang'],
        ];
        $recentFindings = [
            ['title' => 'Missing plan 3 hari berturut', 'user' => 'Budi Santoso', 'severity' => 'major', 'date' => '7 Jul 2025'],
            ['title' => 'Late submission > 3x seminggu', 'user' => 'Rudi Hermawan', 'severity' => 'medium', 'date' => '6 Jul 2025'],
            ['title' => 'Format plan tidak sesuai', 'user' => 'Eko Prasetyo', 'severity' => 'minor', 'date' => '5 Jul 2025'],
        ];
    @endphp

    <x-ui.page-header title="Ringkasan Divisi">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <input type="date" class="input w-36" value="2025-07-01" />
                <span class="text-muted">—</span>
                <input type="date" class="input w-36" value="2025-07-07" />
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-ui.summary-card label="Health Score" :value="$summaryCards['health_score'] . '/100'" :border="$hc" />
        <x-ui.summary-card label="Entry Hari Ini" :value="$summaryCards['total_entries_today']" context="Total entries" />
        <x-ui.summary-card label="On-Time Rate" :value="$summaryCards['on_time_rate']" context="Minggu ini" :border="$summaryCards['on_time_rate'] >= '80%' ? 'success' : 'warning'" />
        <x-ui.summary-card label="Temuan Minggu Ini" :value="$summaryCards['findings_this_week']" context="Exception" :border="$summaryCards['findings_this_week'] > 0 ? 'danger' : null" />
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Trend Exception Divisi</h3>
            {{-- TODO: ApexCharts $exceptionTrendData --}}
            <div id="chart-hod-exception-trend" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Exception Trend Chart</p>
            </div>
        </x-ui.card>
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Compliance Rate</h3>
            {{-- TODO: ApexCharts $complianceData --}}
            <div id="chart-hod-compliance" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Compliance Chart</p>
            </div>
        </x-ui.card>
    </div>

    {{-- Two columns: Manager Attention + Recent Findings --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Manager Perlu Perhatian</h3>
            @forelse($managersAttention as $mgr)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-text">{{ $mgr['name'] }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ $mgr['detail'] }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui.severity-badge :severity="$mgr['severity']" />
                        <span class="text-xs text-muted">{{ $mgr['findings'] }}x</span>
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="Tidak ada manager yang perlu perhatian" icon="check" class="py-8" />
            @endforelse
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Temuan Terbaru</h3>
            @foreach($recentFindings as $finding)
                <div class="py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-medium text-text">{{ $finding['title'] }}</p>
                        <x-ui.severity-badge :severity="$finding['severity']" />
                    </div>
                    <p class="text-xs text-muted mt-1">{{ $finding['user'] }} · {{ $finding['date'] }}</p>
                </div>
            @endforeach
        </x-ui.card>
    </div>
</x-layouts.app>
