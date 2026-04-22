{{--
    Director Divisions Page
    Route: /director/divisions
    Component: App\Livewire\Director\DivisionsPage
--}}

<x-layouts.app title="Divisi">
    @php
        $divisions = \App\Models\Division::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['id' => (int) $d->id, 'name' => $d->name])
            ->all();

        $selectedDivision = (int) (request('division') ?: ($divisions[0]['id'] ?? 0));
        if (! in_array($selectedDivision, array_column($divisions, 'id'), true)) {
            $selectedDivision = (int) ($divisions[0]['id'] ?? 0);
        }

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

        $workdays = [];
        $cursor = $periodFrom->copy()->startOfDay();
        while ($cursor->lte($periodTo)) {
            if (! $cursor->isWeekend()) {
                $workdays[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        $divisionHealthScore = (int) (\App\Models\HealthScore::query()
            ->where('scope_type', 'division')
            ->where('scope_id', $selectedDivision)
            ->whereDate('score_date', $periodTo->toDateString())
            ->value('score') ?? 0);

        $totalFindings = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->where('division_id', $selectedDivision)
            ->count();

        $divisionUserIds = \App\Models\User::query()
            ->where('division_id', $selectedDivision)
            ->whereIn('role', ['hod', 'manager'])
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        $missingEntries = \App\Models\DailyEntry::query()
            ->whereIn('user_id', $divisionUserIds)
            ->whereIn('entry_date', $workdays)
            ->where(function ($q) {
                $q->where('plan_status', 'missing')->orWhere('realization_status', 'missing');
            })
            ->count();

        $summaryCards = [
            'health_score' => $divisionHealthScore,
            'total_findings' => $totalFindings,
            'missing_entries' => $missingEntries,
            'stagnant_roadmap' => 0,
        ];

        $findingByUser = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->where('division_id', $selectedDivision)
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $userNameById = \App\Models\User::query()->whereIn('id', $findingByUser->pluck('user_id')->all())->pluck('name', 'id')->all();
        $userRoleById = \App\Models\User::query()->whereIn('id', $findingByUser->pluck('user_id')->all())->pluck('role', 'id')->all();

        $userMaxSeverity = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->where('division_id', $selectedDivision)
            ->selectRaw("user_id, max(case when severity='high' then 2 when severity='medium' then 1 else 0 end) as sev")
            ->groupBy('user_id')
            ->pluck('sev', 'user_id')
            ->all();

        $peopleWithFindings = $findingByUser->map(function ($row) use ($userNameById, $userRoleById, $userMaxSeverity) {
            $sev = (int) ($userMaxSeverity[$row->user_id] ?? 0);
            $severity = $sev >= 2 ? 'major' : ($sev === 1 ? 'medium' : 'minor');
            $role = ($userRoleById[$row->user_id] ?? 'manager') === 'hod' ? 'HoD' : 'Manager';

            return [
                'name' => $userNameById[$row->user_id] ?? '—',
                'role' => $role,
                'findings' => (int) $row->total,
                'severity' => $severity,
            ];
        })->all();

        $latestMajorFindings = \App\Models\Finding::query()
            ->with(['user:id,name'])
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->where('division_id', $selectedDivision)
            ->orderByDesc('finding_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($f) {
                $severity = $f->severity === 'high' ? 'major' : ($f->severity === 'medium' ? 'medium' : 'minor');
                return [
                    'id' => $f->id,
                    'title' => $f->title,
                    'user' => $f->user?->name ?? '—',
                    'severity' => $severity,
                    'rule' => strtoupper($f->type),
                    'date' => \Illuminate\Support\Carbon::parse($f->finding_date)->translatedFormat('j M Y'),
                ];
            })
            ->all();

        $stagnantRoadmapItems = [];

        $findingByDate = \App\Models\Finding::query()
            ->whereIn('finding_date', $workdays)
            ->whereIn('severity', ['medium', 'high'])
            ->where('division_id', $selectedDivision)
            ->selectRaw('finding_date, count(*) as total')
            ->groupBy('finding_date')
            ->pluck('total', 'finding_date')
            ->all();

        $exceptionSeries = collect($workdays)->map(fn ($d) => (int) ($findingByDate[$d] ?? 0))->all();

        $requiredPerDay = max(count($divisionUserIds), 1);
        $onTimeCounts = \App\Models\DailyEntry::query()
            ->whereIn('user_id', $divisionUserIds)
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

        $chartCategories = collect($workdays)
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('j M'))
            ->all();
    @endphp

    <x-ui.page-header title="Divisi" description="Analisis detail per divisi" />

    {{-- Division selector + date range --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
        <div class="w-48">
            <label class="label">Divisi</label>
            <select class="input" name="division">
                @foreach($divisions as $div)
                    <option value="{{ $div['id'] }}" {{ $div['id'] === $selectedDivision ? 'selected' : '' }}>{{ $div['name'] }}</option>
                @endforeach
            </select>
        </div>
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

    {{-- Summary cards --}}
    @php $hc = $summaryCards['health_score'] >= 70 ? 'success' : ($summaryCards['health_score'] >= 40 ? 'warning' : 'danger'); @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-ui.summary-card label="Health Score Divisi" :value="$summaryCards['health_score'] . '/100'" :border="$hc" />
        <x-ui.summary-card label="Total Temuan" :value="$summaryCards['total_findings']" context="Periode ini" border="danger" />
        <x-ui.summary-card label="Missing Entries" :value="$summaryCards['missing_entries']" border="warning" />
        <x-ui.summary-card label="Roadmap Tidak Bergerak" :value="$summaryCards['stagnant_roadmap']" border="warning" />
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-4">Trend Exception Divisi</h3>
            <div id="chart-div-exception" class="h-[200px] md:h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
            </div>
        </x-ui.card>
        <x-ui.card class="hidden lg:block">
            <h3 class="text-sm font-semibold text-text mb-4">Compliance Rate</h3>
            <div id="chart-div-compliance" class="h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">Chart akan muncul di sini</p>
            </div>
        </x-ui.card>
    </div>

    <div x-data="{ drawerOpen: false, selectedFinding: null }">
        {{-- Three columns: People, Major findings, Stagnant roadmap --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {{-- People with findings --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-text mb-4">Orang dengan Temuan</h3>
                @foreach($peopleWithFindings as $person)
                    <div class="flex items-center justify-between py-2.5 {{ !$loop->last ? 'border-b border-border' : '' }} cursor-pointer hover:bg-app-bg -mx-4 md:-mx-5 px-4 md:px-5 transition-colors"
                        @click="drawerOpen = true; selectedFinding = { title: '{{ $person['name'] }}', type: 'person' }">
                        <div>
                            <p class="text-sm font-medium text-text">{{ $person['name'] }}</p>
                            <p class="text-sm text-muted">{{ $person['role'] }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-ui.severity-badge :severity="$person['severity']" />
                            <span class="text-sm text-muted">{{ $person['findings'] }}x</span>
                        </div>
                    </div>
                @endforeach
            </x-ui.card>

            {{-- Latest major findings --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-text mb-4">Temuan Besar Terbaru</h3>
                @foreach($latestMajorFindings as $finding)
                    <div class="py-2.5 {{ !$loop->last ? 'border-b border-border' : '' }} cursor-pointer hover:bg-app-bg -mx-4 md:-mx-5 px-4 md:px-5 transition-colors"
                        @click="drawerOpen = true; selectedFinding = {{ json_encode($finding) }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-text">{{ $finding['title'] }}</p>
                            <x-ui.severity-badge :severity="$finding['severity']" />
                        </div>
                        <p class="text-sm text-muted mt-1">{{ $finding['user'] }} · {{ $finding['date'] }}</p>
                        <p class="text-sm text-primary mt-0.5">{{ $finding['rule'] }}</p>
                    </div>
                @endforeach
            </x-ui.card>

            {{-- Stagnant Roadmap --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-text mb-4">Roadmap Tidak Bergerak</h3>
                @foreach($stagnantRoadmapItems as $item)
                    <div class="py-2.5 {{ !$loop->last ? 'border-b border-border' : '' }}">
                        <p class="text-sm font-medium text-text">{{ $item['title'] }}</p>
                        <p class="text-sm text-muted mt-0.5">{{ $item['big_rock'] }}</p>
                        <div class="flex items-center gap-2 mt-1.5">
                            <x-ui.status-badge :status="$item['status']" />
                            <span class="text-sm text-danger font-medium">{{ $item['days_stagnant'] }} hari tanpa aktivitas</span>
                        </div>
                    </div>
                @endforeach
            </x-ui.card>
        </div>

        {{-- Detail Drawer --}}
        <div x-show="drawerOpen" class="fixed inset-0 z-40 flex justify-end" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="drawerOpen = false"></div>
            <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0">
                <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface z-10">
                    <h3 class="font-semibold text-text">Detail Temuan</h3>
                    <button @click="drawerOpen = false" class="text-muted hover:text-text">✕</button>
                </div>
                <div class="p-5">
                    {{-- TODO: Include finding-detail-panel component --}}
                    {{-- Hierarchy chain placeholder --}}
                    <div class="space-y-4">
                        <div class="bg-primary-light border border-primary/20 rounded-lg p-3">
                            <p class="text-sm font-semibold text-primary uppercase tracking-wide">Big Rock</p>
                            <p class="text-sm font-medium text-text mt-1">Optimasi Proses</p>
                        </div>
                        <div class="flex justify-center"><svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3">
                            <p class="text-sm font-semibold text-muted uppercase tracking-wide">Roadmap</p>
                            <p class="text-sm font-medium text-text mt-1">Implementasi SOP Baru</p>
                            <x-ui.status-badge status="in_progress" class="mt-1" />
                        </div>
                        <div class="flex justify-center"><svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3">
                            <p class="text-sm font-semibold text-muted uppercase tracking-wide">Plan</p>
                            <p class="text-sm font-medium text-text mt-1">Review dokumen procurement</p>
                            <p class="text-sm text-muted mt-1">Submitted: 7 Jul 2025, 08:30</p>
                        </div>
                        <div class="flex justify-center"><svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3">
                            <p class="text-sm font-semibold text-muted uppercase tracking-wide">Realization</p>
                            <p class="text-sm font-medium text-text mt-1">Belum diisi</p>
                            <x-ui.status-badge status="missing" class="mt-1" />
                        </div>

                        {{-- Triggered Rules --}}
                        <div class="mt-4 pt-4 border-t border-border">
                            <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-3">Triggered Rules</p>
                            <div class="space-y-2">
                                <div class="flex items-start gap-2 bg-danger-bg rounded-lg p-3">
                                    <x-ui.severity-badge severity="major" />
                                    <p class="text-sm text-text">Missing submission > 2 hari berturut-turut</p>
                                </div>
                            </div>
                        </div>

                        {{-- Timestamps --}}
                        <div class="mt-4 pt-4 border-t border-border">
                            <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Timestamps</p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div><p class="text-sm text-muted">Submitted</p><p class="text-text">7 Jul 2025, 08:30</p></div>
                                <div><p class="text-sm text-muted">Window</p><p class="text-text">08:00 – 17:00</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) return;

    const categories = @json($chartCategories ?? []);
    const exceptions = @json($exceptionSeries ?? []);
    const compliance = @json($complianceSeries ?? []);

    const base = {
        chart: { toolbar: { show: false }, fontFamily: 'Inter, ui-sans-serif, system-ui' },
        grid: { borderColor: 'rgba(226,230,234,0.7)' },
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
    };

    const primary = '#1E3A5F';
    const success = '#1A7F4B';

    window.__daytaCharts = window.__daytaCharts || {};

    function renderLineChart(elId, color, seriesName, seriesData, yMax) {
        const el = document.querySelector(elId);
        if (!el) return;

        if (window.__daytaCharts[elId]) {
            try { window.__daytaCharts[elId].destroy(); } catch (e) {}
        }

        el.innerHTML = '';
        window.__daytaCharts[elId] = new ApexCharts(el, {
            ...base,
            chart: { ...base.chart, type: 'line', height: '100%' },
            colors: [color],
            stroke: { width: 3, curve: 'smooth' },
            series: [{ name: seriesName, data: seriesData }],
            xaxis: { categories, labels: { rotate: -30 } },
            yaxis: yMax ? { min: 0, max: yMax } : { min: 0, forceNiceScale: true },
            tooltip: { shared: true },
        });
        window.__daytaCharts[elId].render();
    }

    renderLineChart('#chart-div-exception', primary, 'Exception', exceptions);
    renderLineChart('#chart-div-compliance', success, 'Compliance %', compliance, 100);
});
</script>
