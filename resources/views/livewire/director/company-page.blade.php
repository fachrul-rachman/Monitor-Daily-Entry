{{--
    Director Company Page
    Route: /director/company
    Component: App\Livewire\Director\CompanyPage
--}}

<x-layouts.app title="Company">
    @php
        // MVP: periode default 7 hari kerja terakhir yang sudah dihitung metrics-nya.
        $latestScoreDate = \App\Models\HealthScore::query()
            ->where('scope_type', 'company')
            ->max('score_date');

        $defaultTo = $latestScoreDate ? \Illuminate\Support\Carbon::parse($latestScoreDate) : \Illuminate\Support\Carbon::yesterday();
        $defaultFrom = $defaultTo->copy()->subDays(7);

        $periodFrom = $defaultFrom;
        $periodTo = $defaultTo;
        try {
            if (request('from')) $periodFrom = \Illuminate\Support\Carbon::parse(request('from'));
            if (request('to')) $periodTo = \Illuminate\Support\Carbon::parse(request('to'));
        } catch (\Throwable $e) {
            $periodFrom = $defaultFrom;
            $periodTo = $defaultTo;
        }

        if ($periodFrom->gt($periodTo)) {
            [$periodFrom, $periodTo] = [$periodTo, $periodFrom];
        }

        $companyScore = \App\Models\HealthScore::query()
            ->where('scope_type', 'company')
            ->whereDate('score_date', $periodTo->toDateString())
            ->value('score');

        $companyScore = $companyScore ?? 0;

        $findingsQuery = \App\Models\Finding::query()
            ->with(['user:id,name', 'division:id,name'])
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()]);

        $totalExceptions = (clone $findingsQuery)->count();
        $highCount = (clone $findingsQuery)->where('severity', 'high')->count();
        $mediumCount = (clone $findingsQuery)->where('severity', 'medium')->count();
        $lowCount = (clone $findingsQuery)->where('severity', 'low')->count();

        // On-time rate (sederhana untuk MVP): tidak late dan tidak missing (plan & realisasi) di hari kerja.
        $activeUserIds = \App\Models\User::query()
            ->whereIn('role', ['hod', 'manager'])
            ->where('status', 'active')
            ->pluck('id')
            ->all();
        $usersCount = count($activeUserIds);
        $workdays = [];
        $cursor = $periodFrom->copy()->startOfDay();
        while ($cursor->lte($periodTo)) {
            if (! $cursor->isWeekend()) {
                $workdays[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }
        $required = max($usersCount * max(count($workdays), 1), 1);
        $onTime = \App\Models\DailyEntry::query()
            ->whereIn('user_id', $activeUserIds)
            ->whereIn('entry_date', $workdays)
            ->whereNotIn('plan_status', ['late', 'missing'])
            ->whereNotIn('realization_status', ['late', 'missing'])
            ->count();
        $onTimeRate = (int) round(($onTime / $required) * 100).'%';

        $summaryMetrics = [
            'health' => (int) $companyScore,
            'total_exceptions' => $totalExceptions,
            // UI legacy: major/medium/minor -> map dari high/medium/low
            'major' => $highCount,
            'medium' => $mediumCount,
            'minor' => $lowCount,
            'on_time_rate' => $onTimeRate,
        ];

        $recentFindings = (clone $findingsQuery)
            ->whereIn('severity', ['medium', 'high'])
            ->orderByDesc('finding_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($f) {
                $severity = $f->severity === 'high' ? 'major' : ($f->severity === 'medium' ? 'medium' : 'minor');
                return [
                    'title' => $f->title,
                    'user' => $f->user?->name ?? '—',
                    'division' => $f->division?->name ?? '—',
                    'severity' => $severity,
                    'date' => \Illuminate\Support\Carbon::parse($f->finding_date)->translatedFormat('j M Y'),
                ];
            })
            ->all();

        $divisionRows = (clone $findingsQuery)
            ->whereIn('severity', ['medium', 'high'])
            ->whereNotNull('division_id')
            ->selectRaw('division_id, count(*) as total')
            ->groupBy('division_id')
            ->orderByDesc('total')
            ->get();

        $divisionNameById = \App\Models\Division::query()
            ->whereIn('id', $divisionRows->pluck('division_id')->all())
            ->pluck('name', 'id')
            ->all();

        $divisionTotal = max((int) $divisionRows->sum('total'), 1);
        $divisionContributions = $divisionRows->map(function ($row) use ($divisionTotal, $divisionNameById) {
            $divisionName = $divisionNameById[$row->division_id] ?? '—';
            $percentage = (int) round(($row->total / $divisionTotal) * 100);

            return [
                'name' => $divisionName,
                'findings' => (int) $row->total,
                'percentage' => $percentage,
            ];
        })->all();

        // Chart data
        $companyScoresByDate = \App\Models\HealthScore::query()
            ->where('scope_type', 'company')
            ->whereBetween('score_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->pluck('score', 'score_date')
            ->all();

        $healthSeries = collect($workdays)->map(fn ($d) => (int) ($companyScoresByDate[$d] ?? 0))->all();

        $requiredPerDay = max($usersCount, 1);
        $onTimeCounts = \App\Models\DailyEntry::query()
            ->whereIn('user_id', $activeUserIds)
            ->whereIn('entry_date', $workdays)
            ->whereNotIn('plan_status', ['late', 'missing'])
            ->whereNotIn('realization_status', ['late', 'missing'])
            ->selectRaw('entry_date, count(*) as total')
            ->groupBy('entry_date')
            ->pluck('total', 'entry_date')
            ->all();

        $complianceSeries = collect($workdays)
            ->map(fn ($d) => (int) round(((int) ($onTimeCounts[$d] ?? 0) / $requiredPerDay) * 100))
            ->all();

        $categoryCounts = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->pluck('total', 'type')
            ->all();

        $categoryLabelMap = [
            'missing_daily' => 'Missing Entry',
            'late_weekly' => 'Terlambat Berulang',
            'repetitive_5days' => 'Isian Berulang 5 Hari',
        ];

        $categoryLabels = [];
        $categorySeries = [];
        foreach ($categoryCounts as $type => $count) {
            $categoryLabels[] = $categoryLabelMap[$type] ?? strtoupper((string) $type);
            $categorySeries[] = (int) $count;
        }

        $chartCategories = collect($workdays)
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('j M'))
            ->all();
    @endphp

    {{-- Page Header --}}
    <x-ui.page-header title="Company">
        <x-slot:actions>
            <form method="GET" class="flex items-end gap-2">
                <div class="w-40">
                    <label class="label">Dari</label>
                    <input type="date" class="input" name="from" value="{{ $periodFrom->toDateString() }}" />
                </div>
                <div class="w-40">
                    <label class="label">Sampai</label>
                    <input type="date" class="input" name="to" value="{{ $periodTo->toDateString() }}" />
                </div>
                <button type="submit" class="btn-secondary px-4">Terapkan</button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Summary Metrics Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php $hc = $summaryMetrics['health'] >= 70 ? 'success' : ($summaryMetrics['health'] >= 40 ? 'warning' : 'danger'); @endphp
        <x-ui.summary-card label="Company Health" :value="$summaryMetrics['health'] . '/100'" :border="$hc" />
        <x-ui.summary-card label="Total Exception" :value="$summaryMetrics['total_exceptions']" context="Periode ini" border="danger" />
        <x-ui.card>
            <p class="text-sm text-muted font-medium">Severity Breakdown</p>
            <div class="flex flex-wrap items-center gap-2 mt-2">
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
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
            </div>
        </x-ui.card>
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Compliance Trend</h3>
            {{-- TODO: ApexCharts $complianceTrendData --}}
            <div id="chart-compliance-trend" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
            </div>
        </x-ui.card>
    </div>
    <div class="mb-8">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Distribusi Kategori Exception</h3>
            {{-- TODO: ApexCharts $categoryDistributionData --}}
            <div id="chart-category-distribution" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
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
                    <p class="text-xs text-muted mt-1">{{ $finding['user'] }} &middot; {{ $finding['division'] }} &middot; {{ $finding['date'] }}</p>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const categories = @json($chartCategories ?? []);
    const health = @json($healthSeries ?? []);
    const compliance = @json($complianceSeries ?? []);
    const catLabels = @json($categoryLabels ?? []);
    const catSeries = @json($categorySeries ?? []);

    const base = {
        chart: { toolbar: { show: false }, fontFamily: 'Inter, ui-sans-serif, system-ui' },
        grid: { borderColor: 'rgba(226,230,234,0.7)' },
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
    };

    const primary = '#1E3A5F';
    const success = '#1A7F4B';
    const palette = ['#1E3A5F', '#B91C1C', '#B45309', '#1D4ED8', '#1A7F4B'];

    window.__daytaCharts = window.__daytaCharts || {};

    function renderChart(elId, chartFactory) {
        const el = document.querySelector(elId);
        if (!el) return;

        if (window.__daytaCharts[elId]) {
            try { window.__daytaCharts[elId].destroy(); } catch (e) {}
        }

        el.innerHTML = '';
        window.__daytaCharts[elId] = chartFactory(el);
        window.__daytaCharts[elId].render();
    }

    renderChart('#chart-health-trend', (el) => new ApexCharts(el, {
        ...base,
        chart: { ...base.chart, type: 'line', height: '100%' },
        colors: [primary],
        stroke: { width: 3, curve: 'smooth' },
        series: [{ name: 'Health', data: health }],
        xaxis: { categories, labels: { rotate: -30 } },
        yaxis: { min: 0, max: 100 },
        tooltip: { shared: true },
    }));

    renderChart('#chart-compliance-trend', (el) => new ApexCharts(el, {
        ...base,
        chart: { ...base.chart, type: 'line', height: '100%' },
        colors: [success],
        stroke: { width: 3, curve: 'smooth' },
        series: [{ name: 'Compliance %', data: compliance }],
        xaxis: { categories, labels: { rotate: -30 } },
        yaxis: { min: 0, max: 100 },
        tooltip: { shared: true },
    }));

    renderChart('#chart-category-distribution', (el) => new ApexCharts(el, {
        ...base,
        chart: { ...base.chart, type: 'donut', height: '100%' },
        labels: catLabels.length ? catLabels : ['Belum ada data'],
        colors: palette.slice(0, Math.max(catLabels.length, 1)),
        series: catSeries.length ? catSeries : [1],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true },
        tooltip: { y: { formatter: (v) => `${v} temuan` } },
    }));
});
</script>
