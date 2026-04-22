{{--
    HoD Division Summary Page
    Route: /hod/division-summary
    Component: App\Livewire\Hod\DivisionSummaryPage
--}}

<x-layouts.app title="Ringkasan Divisi">
    @php
        $hod = auth()->user();

        $latestScoreDate = \App\Models\HealthScore::query()
            ->where('scope_type', 'division')
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

        $assignedDivisionIds = \App\Models\HodAssignment::query()
            ->where('hod_id', $hod->id)
            ->pluck('division_id')
            ->filter()
            ->values()
            ->all();

        if (empty($assignedDivisionIds) && $hod->division_id) {
            $assignedDivisionIds = [(int) $hod->division_id];
        }

        $managerUsers = \App\Models\User::query()
            ->whereIn('division_id', $assignedDivisionIds)
            ->where('role', 'manager')
            ->where('status', 'active')
            ->get(['id', 'name', 'division_id']);

        $userIds = $managerUsers->pluck('id')->push($hod->id)->unique()->values()->all();

        $workdays = [];
        $cursor = $periodFrom->copy()->startOfDay();
        while ($cursor->lte($periodTo)) {
            if (! $cursor->isWeekend()) {
                $workdays[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        $divisionScores = \App\Models\HealthScore::query()
            ->where('scope_type', 'division')
            ->whereDate('score_date', $periodTo->toDateString())
            ->whereIn('scope_id', $assignedDivisionIds)
            ->pluck('score');

        $healthScore = $divisionScores->isNotEmpty() ? (int) round($divisionScores->avg()) : 0;
        $hc = $healthScore >= 70 ? 'success' : ($healthScore >= 40 ? 'warning' : 'danger');

        $today = \Illuminate\Support\Carbon::today()->toDateString();
        $todayEntries = \App\Models\DailyEntry::query()
            ->whereIn('user_id', $userIds)
            ->whereDate('entry_date', $today)
            ->pluck('id')
            ->all();

        $totalEntriesToday = empty($todayEntries)
            ? 0
            : \App\Models\DailyEntryItem::query()->whereIn('daily_entry_id', $todayEntries)->count();

        $required = max(count($userIds) * max(count($workdays), 1), 1);
        $onTimeCount = \App\Models\DailyEntry::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('entry_date', $workdays)
            ->whereNotIn('plan_status', ['late', 'missing'])
            ->whereNotIn('realization_status', ['late', 'missing'])
            ->count();
        $onTimeRateValue = (int) round(($onTimeCount / $required) * 100);
        $onTimeRate = $onTimeRateValue.'%';

        $findingsThisWeek = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->whereIn('user_id', $managerUsers->pluck('id')->all())
            ->count();

        $summaryCards = [
            'health_score' => $healthScore,
            'total_entries_today' => $totalEntriesToday,
            'on_time_rate' => $onTimeRate,
            'on_time_rate_value' => $onTimeRateValue,
            'findings_this_week' => $findingsThisWeek,
        ];

        $findingRows = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->whereIn('user_id', $managerUsers->pluck('id')->all())
            ->get(['id', 'user_id', 'severity', 'title', 'finding_date'])
            ->groupBy('user_id');

        $lateMissingCounts = \App\Models\DailyEntry::query()
            ->whereIn('user_id', $managerUsers->pluck('id')->all())
            ->whereIn('entry_date', $workdays)
            ->selectRaw('user_id')
            ->selectRaw("sum(case when plan_status='missing' or realization_status='missing' then 1 else 0 end) as missing_count")
            ->selectRaw("sum(case when plan_status='late' or realization_status='late' then 1 else 0 end) as late_count")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $managersAttention = $managerUsers->map(function ($mgr) use ($findingRows, $lateMissingCounts) {
            $rows = $findingRows->get($mgr->id, collect());
            $findings = $rows->count();
            $severity = $rows->contains('severity', 'high') ? 'major' : ($rows->contains('severity', 'medium') ? 'medium' : 'minor');

            $lm = $lateMissingCounts->get($mgr->id);
            $missing = (int) ($lm?->missing_count ?? 0);
            $late = (int) ($lm?->late_count ?? 0);

            $detail = [];
            if ($missing > 0) $detail[] = "Missing {$missing}x";
            if ($late > 0) $detail[] = "Late {$late}x";
            if ($findings > 0) $detail[] = "Finding {$findings}x";

            return [
                'name' => $mgr->name,
                'findings' => $findings,
                'severity' => $severity,
                'detail' => ! empty($detail) ? implode(', ', $detail) : 'Tidak ada temuan',
            ];
        })
        ->sortByDesc('findings')
        ->take(8)
        ->values()
        ->all();

        $recentFindings = \App\Models\Finding::query()
            ->with(['user:id,name'])
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->whereIn('user_id', $managerUsers->pluck('id')->all())
            ->orderByDesc('finding_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($f) {
                $sev = $f->severity === 'high' ? 'major' : ($f->severity === 'medium' ? 'medium' : 'minor');
                return [
                    'title' => $f->title,
                    'user' => $f->user?->name ?? '—',
                    'severity' => $sev,
                    'date' => \Illuminate\Support\Carbon::parse($f->finding_date)->translatedFormat('j M Y'),
                ];
            })
            ->all();

        $findingByDate = \App\Models\Finding::query()
            ->whereIn('finding_date', $workdays)
            ->whereIn('severity', ['medium', 'high'])
            ->whereIn('user_id', $managerUsers->pluck('id')->all())
            ->selectRaw('finding_date, count(*) as total')
            ->groupBy('finding_date')
            ->pluck('total', 'finding_date')
            ->all();

        $exceptionSeries = collect($workdays)->map(fn ($d) => (int) ($findingByDate[$d] ?? 0))->all();

        $complianceSeries = collect($workdays)->map(function ($d) use ($userIds) {
            $required = max(count($userIds), 1);
            $onTime = \App\Models\DailyEntry::query()
                ->whereIn('user_id', $userIds)
                ->whereDate('entry_date', $d)
                ->whereNotIn('plan_status', ['late', 'missing'])
                ->whereNotIn('realization_status', ['late', 'missing'])
                ->count();

            return (int) round(($onTime / $required) * 100);
        })->all();
    @endphp

    <x-ui.page-header title="Ringkasan Divisi">
        <x-slot:actions>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="date" class="input w-36" name="from" value="{{ $periodFrom->toDateString() }}" />
                <span class="text-muted">—</span>
                <input type="date" class="input w-36" name="to" value="{{ $periodTo->toDateString() }}" />
                <button type="submit" class="btn-secondary px-4">Terapkan</button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-ui.summary-card label="Health Score" :value="$summaryCards['health_score'] . '/100'" :border="$hc" />
        <x-ui.summary-card label="Entry Hari Ini" :value="$summaryCards['total_entries_today']" context="Total entries" />
        <x-ui.summary-card label="On-Time Rate" :value="$summaryCards['on_time_rate']" context="Minggu ini" :border="$summaryCards['on_time_rate_value'] >= 80 ? 'success' : 'warning'" />
        <x-ui.summary-card label="Temuan Minggu Ini" :value="$summaryCards['findings_this_week']" context="Exception" :border="$summaryCards['findings_this_week'] > 0 ? 'danger' : null" />
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Trend Exception Divisi</h3>
            {{-- TODO: ApexCharts $exceptionTrendData --}}
            <div id="chart-hod-exception-trend" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
            </div>
        </x-ui.card>
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Compliance Rate</h3>
            {{-- TODO: ApexCharts $complianceData --}}
            <div id="chart-hod-compliance" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
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
                        <p class="text-sm text-muted mt-0.5">{{ $mgr['detail'] }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui.severity-badge :severity="$mgr['severity']" />
                        <span class="text-sm text-muted">{{ $mgr['findings'] }}x</span>
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
                    <p class="text-sm text-muted mt-1">{{ $finding['user'] }} &middot; {{ $finding['date'] }}</p>
                </div>
            @endforeach
        </x-ui.card>
    </div>
</x-layouts.app>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const categories = @json(collect($workdays)->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('D, j M'))->all());
    const exceptions = @json($exceptionSeries ?? []);
    const compliance = @json($complianceSeries ?? []);

    const base = {
        chart: { toolbar: { show: false }, fontFamily: 'Inter, ui-sans-serif, system-ui' },
        grid: { borderColor: 'rgba(226,230,234,0.7)' },
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
    };

    const elExc = document.querySelector('#chart-hod-exception-trend');
    if (elExc) {
        elExc.innerHTML = '';
        new ApexCharts(elExc, {
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

    const elComp = document.querySelector('#chart-hod-compliance');
    if (elComp) {
        elComp.innerHTML = '';
        new ApexCharts(elComp, {
            ...base,
            chart: { ...base.chart, type: 'line', height: '100%' },
            colors: ['#1A7F4B'],
            stroke: { width: 3, curve: 'smooth' },
            series: [{ name: 'Compliance (%)', data: compliance }],
            xaxis: { categories, labels: { rotate: -30 } },
            yaxis: { min: 0, max: 100 },
        }).render();
    }
});
</script>
