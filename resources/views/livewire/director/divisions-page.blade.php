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

        $today = \Illuminate\Support\Carbon::today();
        $defaultTo = $today->copy();
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

        if ($periodTo->gt($today)) {
            $periodTo = $today->copy();
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

        $peopleUserIds = $findingByUser->pluck('user_id')->filter()->values()->all();
        $userNameById = \App\Models\User::query()->whereIn('id', $peopleUserIds)->pluck('name', 'id')->all();
        $userRoleById = \App\Models\User::query()->whereIn('id', $peopleUserIds)->pluck('role', 'id')->all();

        $userMaxSeverity = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->where('division_id', $selectedDivision)
            ->selectRaw("user_id, max(case when severity='high' then 2 when severity='medium' then 1 else 0 end) as sev")
            ->groupBy('user_id')
            ->pluck('sev', 'user_id')
            ->all();

        $latestByUserId = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->where('division_id', $selectedDivision)
            ->whereIn('user_id', $peopleUserIds)
            ->orderByDesc('finding_date')
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'finding_date', 'type', 'severity', 'title'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->take(8)->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'title' => (string) $r->title,
                    'date' => \Illuminate\Support\Carbon::parse($r->finding_date)->translatedFormat('j M Y'),
                    'rule' => strtoupper((string) $r->type),
                    'severity' => $r->severity === 'high' ? 'major' : ($r->severity === 'medium' ? 'medium' : 'minor'),
                ];
            })->values()->all())
            ->all();

        $peopleWithFindings = $findingByUser->map(function ($row) use ($userNameById, $userRoleById, $userMaxSeverity, $latestByUserId) {
            $sev = (int) ($userMaxSeverity[$row->user_id] ?? 0);
            $severity = $sev >= 2 ? 'major' : ($sev === 1 ? 'medium' : 'minor');
            $role = ($userRoleById[$row->user_id] ?? 'manager') === 'hod' ? 'HoD' : 'Manager';

            return [
                'type' => 'person',
                'user_id' => (int) $row->user_id,
                'name' => $userNameById[$row->user_id] ?? '—',
                'role' => $role,
                'findings' => (int) $row->total,
                'severity' => $severity,
                'latest_findings' => $latestByUserId[(int) $row->user_id] ?? [],
            ];
        })->all();

        $setting = \App\Models\ReportSetting::current();

        $toTimeText = function ($value): ?string {
            if (! $value) {
                return null;
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->translatedFormat('j M Y, H:i');
            } catch (\Throwable) {
                return null;
            }
        };

        $buildEntryChain = function (?int $userId, ?string $date) use ($setting, $toTimeText): array {
            if (! $userId || ! $date) {
                return [];
            }

            $entry = \App\Models\DailyEntry::query()
                ->with([
                    'items.bigRock:id,title',
                    'items.roadmapItem:id,title,status',
                ])
                ->where('user_id', $userId)
                ->whereDate('entry_date', $date)
                ->first(['id', 'user_id', 'entry_date', 'plan_status', 'realization_status', 'plan_submitted_at', 'realization_submitted_at', 'plan_title', 'plan_text', 'realization_text', 'realization_reason']);

            $window = [
                'plan' => $setting ? sprintf('%s–%s', (string) $setting->plan_open_time, (string) $setting->plan_close_time) : null,
                'realization' => $setting ? sprintf('%s–%s', (string) $setting->realization_open_time, (string) $setting->realization_close_time) : null,
            ];

            if (! $entry) {
                return [
                    'big_rock' => null,
                    'roadmap' => null,
                    'plan' => null,
                    'realization' => null,
                    'timestamps' => [
                        'plan_submitted_at' => null,
                        'realization_submitted_at' => null,
                        'window' => $window,
                    ],
                ];
            }

            $items = $entry->items ?? collect();
            $pick = $items->firstWhere('roadmap_item_id', '!=', null)
                ?? $items->firstWhere('big_rock_id', '!=', null)
                ?? $items->first();

            $bigRockTitle = $pick?->bigRock?->title ?? null;
            $roadmapTitle = $pick?->roadmapItem?->title ?? null;
            $roadmapStatus = $pick?->roadmapItem?->status ?? null;

            $planTitle = $pick?->plan_title ?: ($entry->plan_title ?: null);
            $planText = $pick?->plan_text ?: ($entry->plan_text ?: null);

            $realStatus = ($pick?->realization_status && $pick?->realization_status !== 'draft') ? $pick->realization_status : null;
            $realText = $pick?->realization_text ?: ($entry->realization_text ?: null);
            $realReason = $pick?->realization_reason ?: ($entry->realization_reason ?: null);

            return [
                'big_rock' => $bigRockTitle ? ['title' => (string) $bigRockTitle] : null,
                'roadmap' => $roadmapTitle ? ['title' => (string) $roadmapTitle, 'status' => $roadmapStatus ?: null] : null,
                'plan' => ($planTitle || $planText) ? ['title' => $planTitle ?: '—', 'text' => $planText ?: '', 'status' => $entry->plan_status ?: null] : null,
                'realization' => ($realStatus || $realText || $realReason) ? ['status' => $realStatus ?: ($entry->realization_status ?: null), 'text' => $realText ?: '', 'reason' => $realReason ?: ''] : null,
                'timestamps' => [
                    'plan_submitted_at' => $toTimeText($entry->plan_submitted_at?->toDateTimeString()),
                    'realization_submitted_at' => $toTimeText($entry->realization_submitted_at?->toDateTimeString()),
                    'window' => $window,
                ],
            ];
        };

        $latestMajorFindings = \App\Models\Finding::query()
            ->with(['user:id,name'])
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->where('division_id', $selectedDivision)
            ->orderByDesc('finding_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($f) use ($buildEntryChain) {
                $severity = $f->severity === 'high' ? 'major' : ($f->severity === 'medium' ? 'medium' : 'minor');
                return [
                    'type' => 'finding',
                    'id' => $f->id,
                    'title' => $f->title,
                    'user' => $f->user?->name ?? '—',
                    'user_id' => $f->user_id ? (int) $f->user_id : null,
                    'severity' => $severity,
                    'rule' => strtoupper($f->type),
                    'date' => \Illuminate\Support\Carbon::parse($f->finding_date)->translatedFormat('j M Y'),
                    'raw_date' => \Illuminate\Support\Carbon::parse($f->finding_date)->toDateString(),
                    'description' => $f->description ?: '',
                    'chain' => $buildEntryChain($f->user_id ? (int) $f->user_id : null, \Illuminate\Support\Carbon::parse($f->finding_date)->toDateString()),
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
                        @click="drawerOpen = true; selectedFinding = {{ json_encode($person) }}">
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
                    <template x-if="selectedFinding && selectedFinding.type === 'finding'">
                        <div class="space-y-4">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-base font-semibold text-text" x-text="selectedFinding.title"></p>
                                    <span class="text-xs font-semibold px-2 py-1 rounded bg-app-bg text-text" x-text="selectedFinding.rule"></span>
                                </div>
                                <p class="text-sm text-muted mt-1" x-text="`${selectedFinding.user} · ${selectedFinding.date}`"></p>
                                <template x-if="selectedFinding.description">
                                    <p class="text-sm text-text mt-2 whitespace-pre-line" x-text="selectedFinding.description"></p>
                                </template>
                            </div>

                            <template x-if="selectedFinding.chain && selectedFinding.chain.big_rock">
                                <div class="bg-primary-light border border-primary/20 rounded-lg p-3">
                                    <p class="text-sm font-semibold text-primary uppercase tracking-wide">Big Rock</p>
                                    <p class="text-sm font-medium text-text mt-1" x-text="selectedFinding.chain.big_rock.title"></p>
                                </div>
                            </template>

                            <template x-if="selectedFinding.chain && selectedFinding.chain.roadmap">
                                <div class="bg-app-bg border border-border rounded-lg p-3">
                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide">Roadmap</p>
                                    <p class="text-sm font-medium text-text mt-1" x-text="selectedFinding.chain.roadmap.title"></p>
                                    <template x-if="selectedFinding.chain.roadmap.status">
                                        <p class="text-sm text-muted mt-1" x-text="`Status: ${selectedFinding.chain.roadmap.status}`"></p>
                                    </template>
                                </div>
                            </template>

                            <template x-if="selectedFinding.chain && selectedFinding.chain.plan">
                                <div class="bg-app-bg border border-border rounded-lg p-3">
                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide">Plan</p>
                                    <p class="text-sm font-medium text-text mt-1" x-text="selectedFinding.chain.plan.title"></p>
                                    <template x-if="selectedFinding.chain.plan.text">
                                        <p class="text-sm text-muted mt-1 whitespace-pre-line" x-text="selectedFinding.chain.plan.text"></p>
                                    </template>
                                </div>
                            </template>

                            <template x-if="selectedFinding.chain && selectedFinding.chain.realization">
                                <div class="bg-app-bg border border-border rounded-lg p-3">
                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide">Realization</p>
                                    <template x-if="selectedFinding.chain.realization.status">
                                        <p class="text-sm font-medium text-text mt-1" x-text="`Status: ${selectedFinding.chain.realization.status}`"></p>
                                    </template>
                                    <template x-if="selectedFinding.chain.realization.text">
                                        <p class="text-sm text-muted mt-1 whitespace-pre-line" x-text="selectedFinding.chain.realization.text"></p>
                                    </template>
                                    <template x-if="selectedFinding.chain.realization.reason">
                                        <p class="text-sm text-muted mt-1 whitespace-pre-line" x-text="`Reason: ${selectedFinding.chain.realization.reason}`"></p>
                                    </template>
                                </div>
                            </template>

                            <div class="mt-4 pt-4 border-t border-border">
                                <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Timestamps</p>
                                <div class="space-y-1 text-sm">
                                    <template x-if="selectedFinding.chain && selectedFinding.chain.timestamps && selectedFinding.chain.timestamps.plan_submitted_at">
                                        <p class="text-sm text-text" x-text="`Plan submitted: ${selectedFinding.chain.timestamps.plan_submitted_at}`"></p>
                                    </template>
                                    <template x-if="selectedFinding.chain && selectedFinding.chain.timestamps && selectedFinding.chain.timestamps.realization_submitted_at">
                                        <p class="text-sm text-text" x-text="`Realization submitted: ${selectedFinding.chain.timestamps.realization_submitted_at}`"></p>
                                    </template>
                                    <template x-if="selectedFinding.chain && selectedFinding.chain.timestamps && selectedFinding.chain.timestamps.window && selectedFinding.chain.timestamps.window.plan">
                                        <p class="text-sm text-muted" x-text="`Plan window: ${selectedFinding.chain.timestamps.window.plan}`"></p>
                                    </template>
                                    <template x-if="selectedFinding.chain && selectedFinding.chain.timestamps && selectedFinding.chain.timestamps.window && selectedFinding.chain.timestamps.window.realization">
                                        <p class="text-sm text-muted" x-text="`Realization window: ${selectedFinding.chain.timestamps.window.realization}`"></p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="selectedFinding && selectedFinding.type === 'person'">
                        <div class="space-y-4">
                            <div>
                                <p class="text-base font-semibold text-text" x-text="selectedFinding.name"></p>
                                <p class="text-sm text-muted mt-1" x-text="`${selectedFinding.role} · ${selectedFinding.findings} temuan`"></p>
                            </div>

                            <div class="border-t border-border pt-4">
                                <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-3">Temuan Terbaru</p>
                                <template x-if="selectedFinding.latest_findings && selectedFinding.latest_findings.length">
                                    <div class="space-y-2">
                                        <template x-for="f in selectedFinding.latest_findings" :key="f.id">
                                            <div class="bg-app-bg border border-border rounded-lg p-3">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p class="text-sm font-medium text-text" x-text="f.title"></p>
                                                    <span class="text-xs font-semibold px-2 py-1 rounded bg-surface text-text" x-text="f.rule"></span>
                                                </div>
                                                <p class="text-sm text-muted mt-1" x-text="f.date"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!selectedFinding.latest_findings || !selectedFinding.latest_findings.length">
                                    <p class="text-sm text-muted">Tidak ada data.</p>
                                </template>
                            </div>
                        </div>
                    </template>
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
