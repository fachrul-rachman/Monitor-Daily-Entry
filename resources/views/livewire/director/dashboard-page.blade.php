{{--
    Director Dashboard Page
    Route: /director/dashboard
    Component: App\Livewire\Director\DashboardPage
--}}

<x-layouts.app title="Dashboard">
    @php
        // Dummy data
        $selectedDate = '2025-07-07';
        $healthScore = 58;
        $healthLabel = $healthScore >= 70 ? 'Baik' : ($healthScore >= 40 ? 'Perlu Perhatian' : 'Kritis');
        $healthColor = $healthScore >= 70 ? 'success' : ($healthScore >= 40 ? 'warning' : 'danger');
        $summaryCards = [
            'company_health_score' => $healthScore,
            'divisions_with_exceptions' => 3,
            'major_findings_today' => 5,
            'unresolved_recurring' => 2,
        ];
        $attentionDivisions = [
            ['name' => 'Operasional', 'findings' => 8, 'health' => 42],
            ['name' => 'Keuangan', 'findings' => 5, 'health' => 55],
            ['name' => 'IT', 'findings' => 3, 'health' => 68],
        ];
        $recentMajorFindings = [
            ['title' => 'Missing plan submission 3 hari berturut-turut', 'user' => 'Budi Santoso', 'division' => 'Operasional', 'severity' => 'major', 'time' => '2 jam lalu'],
            ['title' => 'Realisasi blocked tanpa alasan jelas', 'user' => 'Ahmad Fauzi', 'division' => 'IT', 'severity' => 'major', 'time' => '4 jam lalu'],
            ['title' => 'Late submission 5 kali dalam minggu ini', 'user' => 'Rudi Hermawan', 'division' => 'Operasional', 'severity' => 'medium', 'time' => '6 jam lalu'],
        ];
    @endphp

    {{-- Page Header --}}
    <x-ui.page-header title="Dashboard">
        <x-slot:actions>
            {{-- TODO: wire:model="selectedDate" --}}
            <input type="date" class="input w-44" value="{{ $selectedDate }}" />
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Health Score — special display --}}
        <x-ui.card class="border-l-4 border-l-{{ $healthColor }}">
            <p class="text-sm text-muted font-medium">Company Health Score</p>
            <div class="flex items-baseline gap-2 mt-1">
                <p class="text-3xl font-bold text-{{ $healthColor }}">{{ $healthScore }}</p>
                <span class="text-sm font-medium text-{{ $healthColor }}">{{ $healthLabel }}</span>
            </div>
            <p class="text-xs text-muted mt-1">dari 100</p>
        </x-ui.card>

        <x-ui.summary-card
            label="Divisi dengan Exception"
            :value="$summaryCards['divisions_with_exceptions']"
            context="Hari ini"
            :border="$summaryCards['divisions_with_exceptions'] > 0 ? 'warning' : null"
        />
        <x-ui.summary-card
            label="Major Findings Hari Ini"
            :value="$summaryCards['major_findings_today']"
            context="Perlu ditindak"
            :border="$summaryCards['major_findings_today'] > 0 ? 'danger' : null"
        />
        <x-ui.summary-card
            label="Temuan Berulang Belum Selesai"
            :value="$summaryCards['unresolved_recurring']"
            context="Recurring"
            :border="$summaryCards['unresolved_recurring'] > 0 ? 'warning' : null"
        />
    </div>

    {{-- Chart Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Exception Trend (visible on all) --}}
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Trend Exception (7 Hari Terakhir)</h3>
            {{-- TODO: Render ApexCharts dengan $exceptionTrendData --}}
            <div id="chart-exception-trend" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Chart akan di-render oleh ApexCharts</p>
            </div>
        </x-ui.card>

        {{-- Severity Distribution (desktop only) --}}
        <x-ui.card class="hidden lg:block">
            <h3 class="text-sm font-semibold text-text mb-4">Distribusi Severity</h3>
            {{-- TODO: Render ApexCharts dengan $severityDistributionData --}}
            <div id="chart-severity-distribution" class="h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Chart akan di-render oleh ApexCharts</p>
            </div>
        </x-ui.card>
    </div>

    {{-- Division Health Comparison (desktop only) --}}
    <div class="hidden lg:block mb-8">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Perbandingan Kesehatan Divisi</h3>
            {{-- TODO: Render ApexCharts bar chart dengan $divisionHealthData --}}
            <div id="chart-division-health" class="h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Chart akan di-render oleh ApexCharts</p>
            </div>
        </x-ui.card>
    </div>

    {{-- Two-column: Attention List + Recent Major Findings --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Divisi Perlu Perhatian --}}
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Divisi Perlu Perhatian</h3>
            @forelse($attentionDivisions as $div)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div class="flex items-center gap-3">
                        <div>
                            {{-- TODO: href ke director/divisions?division=$div --}}
                            <p class="text-sm font-medium text-text hover:text-primary cursor-pointer">{{ $div['name'] }}</p>
                            <p class="text-xs text-muted">Health: {{ $div['health'] }}/100</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge-danger">{{ $div['findings'] }} temuan</span>
                        @php
                            $divHealthColor = $div['health'] >= 70 ? 'text-success' : ($div['health'] >= 40 ? 'text-warning' : 'text-danger');
                        @endphp
                        <span class="text-sm font-bold {{ $divHealthColor }}">{{ $div['health'] }}</span>
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="Tidak ada exception hari ini" icon="check" class="py-8" />
            @endforelse
        </x-ui.card>

        {{-- Recent Major Findings --}}
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Temuan Besar Terbaru</h3>
            @foreach($recentMajorFindings as $finding)
                <div class="py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-medium text-text">{{ $finding['title'] }}</p>
                        <x-ui.severity-badge :severity="$finding['severity']" />
                    </div>
                    <p class="text-xs text-muted mt-1">{{ $finding['user'] }} · {{ $finding['division'] }} · {{ $finding['time'] }}</p>
                </div>
            @endforeach
        </x-ui.card>
    </div>

    {{-- Quick Access --}}
    <div class="flex flex-wrap gap-3">
        {{-- TODO: href ke route masing-masing --}}
        <a href="#" class="btn-primary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Lihat Company
        </a>
        <a href="#" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Lihat Divisi
        </a>
        <a href="#" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Buka AI Chat
        </a>
    </div>
</x-layouts.app>
