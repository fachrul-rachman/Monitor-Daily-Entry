{{--
    Director Company Page
    Route: /director/company
    Component: App\Livewire\Director\CompanyPage
--}}

<x-layouts.app title="Company">
    @php
        $summaryMetrics = [
            'health' => 58,
            'total_exceptions' => 24,
            'major' => 5,
            'medium' => 11,
            'minor' => 8,
            'on_time_rate' => '82%',
        ];
        $recentFindings = [
            ['title' => 'Missing plan 3 hari berturut', 'user' => 'Budi Santoso', 'division' => 'Operasional', 'severity' => 'major', 'date' => '7 Jul 2025'],
            ['title' => 'Blocked realization tanpa alasan', 'user' => 'Ahmad Fauzi', 'division' => 'IT', 'severity' => 'major', 'date' => '7 Jul 2025'],
            ['title' => 'Late submission berulang', 'user' => 'Rudi Hermawan', 'division' => 'Operasional', 'severity' => 'medium', 'date' => '6 Jul 2025'],
        ];
        $divisionContributions = [
            ['name' => 'Operasional', 'findings' => 12, 'percentage' => 50],
            ['name' => 'IT', 'findings' => 7, 'percentage' => 29],
            ['name' => 'Keuangan', 'findings' => 5, 'percentage' => 21],
        ];
    @endphp

    {{-- Page Header --}}
    <x-ui.page-header title="Company">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                {{-- TODO: wire:model="filterDateFrom" --}}
                <input type="date" class="input w-40" value="2025-07-01" />
                <span class="text-muted">—</span>
                {{-- TODO: wire:model="filterDateTo" --}}
                <input type="date" class="input w-40" value="2025-07-07" />
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Summary Metrics Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php $hc = $summaryMetrics['health'] >= 70 ? 'success' : ($summaryMetrics['health'] >= 40 ? 'warning' : 'danger'); @endphp
        <x-ui.summary-card label="Company Health" :value="$summaryMetrics['health'] . '/100'" :border="$hc" />
        <x-ui.summary-card label="Total Exception" :value="$summaryMetrics['total_exceptions']" context="Periode ini" border="danger" />
        <x-ui.card>
            <p class="text-sm text-muted font-medium">Severity Breakdown</p>
            <div class="flex items-center gap-3 mt-2">
                <span class="badge-danger">{{ $summaryMetrics['major'] }} Major</span>
                <span class="badge-warning">{{ $summaryMetrics['medium'] }} Medium</span>
                <span class="badge-info">{{ $summaryMetrics['minor'] }} Minor</span>
            </div>
        </x-ui.card>
        <x-ui.summary-card label="On-Time Reporting Rate" :value="$summaryMetrics['on_time_rate']" context="Rata-rata periode" border="success" />
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Trend Health Score</h3>
            {{-- TODO: ApexCharts $healthTrendData --}}
            <div id="chart-health-trend" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Health Trend Chart</p>
            </div>
        </x-ui.card>
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Compliance Trend</h3>
            {{-- TODO: ApexCharts $complianceTrendData --}}
            <div id="chart-compliance-trend" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Compliance Trend Chart</p>
            </div>
        </x-ui.card>
    </div>
    <div class="mb-8">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Distribusi Kategori Exception</h3>
            {{-- TODO: ApexCharts $categoryDistributionData --}}
            <div id="chart-category-distribution" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Category Distribution Chart</p>
            </div>
        </x-ui.card>
    </div>

    {{-- Two columns: Recent Findings + Division Contribution --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent findings --}}
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Temuan Terbaru Company</h3>
            @foreach($recentFindings as $finding)
                <div class="py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-medium text-text">{{ $finding['title'] }}</p>
                        <x-ui.severity-badge :severity="$finding['severity']" />
                    </div>
                    <p class="text-xs text-muted mt-1">{{ $finding['user'] }} · {{ $finding['division'] }} · {{ $finding['date'] }}</p>
                </div>
            @endforeach
        </x-ui.card>

        {{-- Division Contribution --}}
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Kontribusi Temuan per Divisi</h3>
            @foreach($divisionContributions as $dc)
                <div class="py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-text">{{ $dc['name'] }}</p>
                        <span class="text-sm text-muted">{{ $dc['findings'] }} temuan</span>
                    </div>
                    <div class="w-full h-2 bg-app-bg rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full" style="width: {{ $dc['percentage'] }}%"></div>
                    </div>
                    <p class="text-xs text-muted mt-1">{{ $dc['percentage'] }}% dari total</p>
                </div>
            @endforeach
        </x-ui.card>
    </div>
</x-layouts.app>
