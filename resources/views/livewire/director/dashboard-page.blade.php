{{--
    Director Dashboard Page
    Route: /director/dashboard
    Component: App\Livewire\Director\DashboardPage
--}}

<x-layouts.app title="Dashboard">
    @php
        $latestCompanyScoreDate = \App\Models\HealthScore::query()
            ->where('scope_type', 'company')
            ->max('score_date');

        $today = \Illuminate\Support\Carbon::today();
        $latestDate = $latestCompanyScoreDate ? \Illuminate\Support\Carbon::parse($latestCompanyScoreDate) : \Illuminate\Support\Carbon::yesterday();

        // Filter: gunakan query string (?from=YYYY-MM-DD&to=YYYY-MM-DD)
        $periodTo = $today->copy();
        $periodFrom = $today->copy()->subDays(7);
        try {
            if (request('to')) $periodTo = \Illuminate\Support\Carbon::parse(request('to'));
            if (request('from')) $periodFrom = \Illuminate\Support\Carbon::parse(request('from'));
        } catch (\Throwable $e) {
            $periodTo = $today->copy();
            $periodFrom = $today->copy()->subDays(7);
        }

        if ($periodTo->gt($today)) $periodTo = $today->copy();
        if ($periodFrom->gt($periodTo)) {
            [$periodFrom, $periodTo] = [$periodTo, $periodFrom];
        }

        $scoreDate = $periodTo->copy();

        $healthScore = (int) (\App\Models\HealthScore::query()
            ->where('scope_type', 'company')
            ->whereDate('score_date', $scoreDate->toDateString())
            ->value('score') ?? 0);

        $healthLabel = $healthScore >= 70 ? 'Baik' : ($healthScore >= 40 ? 'Perlu Perhatian' : 'Kritis');
        $healthColor = $healthScore >= 70 ? 'success' : ($healthScore >= 40 ? 'warning' : 'danger');

        // Note: banyak komponen dashboard memakai range ini (ringkasan + chart).

        $todayFindings = \App\Models\Finding::query()
            ->whereDate('finding_date', $scoreDate->toDateString())
            ->whereIn('severity', ['medium', 'high']);

        $divisionsWithExceptions = (clone $todayFindings)
            ->whereNotNull('division_id')
            ->distinct('division_id')
            ->count('division_id');

        $majorFindingsToday = (clone $todayFindings)->where('severity', 'high')->count();

        $summaryCards = [
            'company_health_score' => $healthScore,
            'divisions_with_exceptions' => $divisionsWithExceptions,
            'major_findings_today' => $majorFindingsToday,
            'unresolved_recurring' => 0,
        ];

        $divisionRows = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->whereNotNull('division_id')
            ->selectRaw('division_id, count(*) as total')
            ->groupBy('division_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $divisionNameById = \App\Models\Division::query()
            ->whereIn('id', $divisionRows->pluck('division_id')->all())
            ->pluck('name', 'id')
            ->all();

        // Chart: 7 hari kerja terakhir (exception + severity + health divisi)
        $workdays = [];
        $cursor = $periodFrom->copy()->startOfDay();
        while ($cursor->lte($periodTo)) {
            if (! $cursor->isWeekend()) {
                $workdays[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        $chartCategories = collect($workdays)->map(fn ($d) => $d->translatedFormat('D, j M'))->all();
        $chartDateKeys = collect($workdays)->map(fn ($d) => $d->toDateString())->all();

        $findingByDate = \App\Models\Finding::query()
            ->whereIn('finding_date', $chartDateKeys)
            ->whereIn('severity', ['medium', 'high'])
            ->selectRaw('finding_date, count(*) as total')
            ->groupBy('finding_date')
            ->pluck('total', 'finding_date')
            ->all();

        $exceptionSeries = collect($chartDateKeys)->map(fn ($k) => (int) ($findingByDate[$k] ?? 0))->all();

        $severityRows = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['low', 'medium', 'high'])
            ->selectRaw("sum(case when severity='high' then 1 else 0 end) as high_count")
            ->selectRaw("sum(case when severity='medium' then 1 else 0 end) as medium_count")
            ->selectRaw("sum(case when severity='low' then 1 else 0 end) as low_count")
            ->first();

        $severityDistribution = [
            'high' => (int) ($severityRows?->high_count ?? 0),
            'medium' => (int) ($severityRows?->medium_count ?? 0),
            'low' => (int) ($severityRows?->low_count ?? 0),
        ];

        $divisionScoreRows = \App\Models\HealthScore::query()
            ->where('scope_type', 'division')
            ->whereDate('score_date', $periodTo->toDateString())
            ->get(['scope_id', 'score']);

        $divisionNameById = \App\Models\Division::query()->pluck('name', 'id')->all();
        $divisionHealthCategories = $divisionScoreRows->map(fn ($r) => $divisionNameById[$r->scope_id] ?? '—')->all();
        $divisionHealthSeries = $divisionScoreRows->map(fn ($r) => (int) $r->score)->all();

        $divisionScoreById = \App\Models\HealthScore::query()
            ->where('scope_type', 'division')
            ->whereDate('score_date', $scoreDate->toDateString())
            ->whereIn('scope_id', $divisionRows->pluck('division_id')->all())
            ->pluck('score', 'scope_id')
            ->all();

        $attentionDivisions = $divisionRows->map(function ($row) use ($divisionNameById, $divisionScoreById) {
            return [
                'id' => (int) $row->division_id,
                'name' => $divisionNameById[$row->division_id] ?? '—',
                'findings' => (int) $row->total,
                'health' => (int) ($divisionScoreById[$row->division_id] ?? 0),
            ];
        })->all();

        $recentMajorFindings = \App\Models\Finding::query()
            ->with(['user:id,name', 'division:id,name'])
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->orderByDesc('finding_date')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(function ($f) {
                $severity = $f->severity === 'high' ? 'major' : ($f->severity === 'medium' ? 'medium' : 'minor');

                return [
                    'title' => $f->title,
                    'user' => $f->user?->name ?? '—',
                    'division' => $f->division?->name ?? '—',
                    'severity' => $severity,
                    'time' => \Illuminate\Support\Carbon::parse($f->finding_date)->translatedFormat('j M'),
                ];
            })
            ->all();
    @endphp

    {{-- Page Header --}}
    <x-ui.page-header title="Dashboard">
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

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Health Score — special display --}}
        <x-ui.card class="border-l-4 border-l-{{ $healthColor }}">
            <p class="text-sm text-muted font-medium">Company Health Score</p>
            <div class="flex items-baseline gap-2 mt-1">
                <p class="text-3xl font-bold text-{{ $healthColor }}">{{ $healthScore }}</p>
                <span class="text-sm font-medium text-{{ $healthColor }}">{{ $healthLabel }}</span>
            </div>
            <p class="text-sm text-muted mt-1">dari 100</p>
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
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
            </div>
        </x-ui.card>

        {{-- Severity Distribution (desktop only) --}}
        <x-ui.card class="hidden lg:block">
            <h3 class="text-sm font-semibold text-text mb-4">Distribusi Severity</h3>
            {{-- TODO: Render ApexCharts dengan $severityDistributionData --}}
            <div id="chart-severity-distribution" class="h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
            </div>
        </x-ui.card>
    </div>

    {{-- Division Health Comparison (desktop only) --}}
    <div class="hidden lg:block mb-8">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Perbandingan Kesehatan Divisi</h3>
            {{-- TODO: Render ApexCharts bar chart dengan $divisionHealthData --}}
            <div id="chart-division-health" class="h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
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
                            <a href="{{ route('director.divisions', ['division' => $div['id'], 'from' => $periodFrom->toDateString(), 'to' => $periodTo->toDateString()]) }}"
                               class="text-sm font-medium text-text hover:text-primary">
                                {{ $div['name'] }}
                            </a>
                            <p class="text-sm text-muted">Health: {{ $div['health'] }}/100</p>
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
                    <p class="text-sm text-muted mt-1">{{ $finding['user'] }} &middot; {{ $finding['division'] }} &middot; {{ $finding['time'] }}</p>
                </div>
            @endforeach
        </x-ui.card>
    </div>

    {{-- Quick Access --}}
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('director.company', ['from' => $periodFrom->toDateString(), 'to' => $periodTo->toDateString()]) }}" class="btn-primary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Lihat Company
        </a>
        <a href="{{ route('director.divisions', ['from' => $periodFrom->toDateString(), 'to' => $periodTo->toDateString()]) }}" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Lihat Divisi
        </a>
        <a href="{{ route('director.ai-chat') }}" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Buka AI Chat
        </a>
    </div>
</x-layouts.app>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const categories = @json($chartCategories ?? []);
    const exceptions = @json($exceptionSeries ?? []);
    const severity = @json($severityDistribution ?? ['high' => 0, 'medium' => 0, 'low' => 0]);
    const divCats = @json($divisionHealthCategories ?? []);
    const divScores = @json($divisionHealthSeries ?? []);

    const base = {
        chart: { toolbar: { show: false }, fontFamily: 'Inter, ui-sans-serif, system-ui' },
        grid: { borderColor: 'rgba(226,230,234,0.7)' },
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
    };

    const trendEl = document.querySelector('#chart-exception-trend');
    if (trendEl) {
        trendEl.innerHTML = '';
        new ApexCharts(trendEl, {
            ...base,
            chart: { ...base.chart, type: 'line', height: '100%' },
            colors: ['#1E3A5F'],
            stroke: { width: 3, curve: 'smooth' },
            series: [{ name: 'Exception', data: exceptions }],
            xaxis: { categories, labels: { rotate: -30 } },
            yaxis: { min: 0, forceNiceScale: true },
            tooltip: { shared: true },
        }).render();
    }

    const sevEl = document.querySelector('#chart-severity-distribution');
    if (sevEl) {
        sevEl.innerHTML = '';
        new ApexCharts(sevEl, {
            ...base,
            chart: { ...base.chart, type: 'donut', height: '100%' },
            labels: ['High', 'Medium', 'Low'],
            colors: ['#B91C1C', '#B45309', '#1D4ED8'],
            series: [severity.high || 0, severity.medium || 0, severity.low || 0],
            legend: { position: 'bottom' },
            dataLabels: { enabled: true },
        }).render();
    }

    const divEl = document.querySelector('#chart-division-health');
    if (divEl) {
        divEl.innerHTML = '';
        new ApexCharts(divEl, {
            ...base,
            chart: { ...base.chart, type: 'bar', height: '100%' },
            colors: ['#1A7F4B'],
            series: [{ name: 'Health', data: divScores }],
            xaxis: { categories: divCats, labels: { rotate: -30 } },
            yaxis: { min: 0, max: 100 },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        }).render();
    }
});
</script>
