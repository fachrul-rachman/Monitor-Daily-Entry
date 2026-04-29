{{--
    HoD Dashboard Page
    Route: /hod/dashboard
    Component: App\Livewire\Hod\DashboardPage
--}}

<x-layouts.app title="Dashboard HoD">
    @php
        $today = \Illuminate\Support\Carbon::today();
        $todayDate = $today->translatedFormat('l, j F Y');

        $setting = \App\Models\ReportSetting::current();
        $planWindowInfo = \Illuminate\Support\Carbon::parse($setting->plan_open_time)->format('H:i').' – '.\Illuminate\Support\Carbon::parse($setting->plan_close_time)->format('H:i');
        $realizationWindowInfo = \Illuminate\Support\Carbon::parse($setting->realization_open_time)->format('H:i').' – '.\Illuminate\Support\Carbon::parse($setting->realization_close_time)->format('H:i');

        $hod = auth()->user();

        $assignedDivisionIds = \App\Models\HodAssignment::query()
            ->where('hod_id', $hod->id)
            ->pluck('division_id')
            ->filter()
            ->values()
            ->all();

        if (empty($assignedDivisionIds) && $hod->division_id) {
            $assignedDivisionIds = [(int) $hod->division_id];
        }

        $scoreDate = \App\Models\HealthScore::query()
            ->where('scope_type', 'user')
            ->where('scope_id', $hod->id)
            ->max('score_date');

        $scoreDate = $scoreDate ? \Illuminate\Support\Carbon::parse($scoreDate) : \Illuminate\Support\Carbon::yesterday();

        $periodFrom = $scoreDate->copy()->subDays(7);
        $periodTo = $scoreDate->copy();

        $todayEntry = \App\Models\DailyEntry::query()
            ->where('user_id', $hod->id)
            ->whereDate('entry_date', $today->toDateString())
            ->first(['id', 'plan_status', 'realization_status']);

        $planTodayCount = $todayEntry
            ? \App\Models\DailyEntryItem::query()->where('daily_entry_id', $todayEntry->id)->count()
            : 0;

        $realizationPending = $todayEntry
            ? \App\Models\DailyEntryItem::query()
                ->where('daily_entry_id', $todayEntry->id)
                ->where('realization_status', 'draft')
                ->count()
            : 0;

        // Status banner: dianggap terisi jika sudah ada item (tanpa status draft).
        $planFilled = (bool) ($todayEntry && $planTodayCount > 0);
        $realizationFilled = (bool) ($todayEntry && $planTodayCount > 0 && $realizationPending === 0);

        $divisionManagers = \App\Models\User::query()
            ->whereIn('division_id', $assignedDivisionIds)
            ->where('role', 'manager')
            ->where('status', 'active')
            ->get(['id', 'name']);

        $managerFindingRows = \App\Models\Finding::query()
            ->whereBetween('finding_date', [$periodFrom->toDateString(), $periodTo->toDateString()])
            ->whereIn('severity', ['medium', 'high'])
            ->whereIn('user_id', $divisionManagers->pluck('id')->all())
            ->get(['id', 'user_id', 'severity', 'title', 'finding_date'])
            ->groupBy('user_id');

        $managerFindingsCount = (int) $managerFindingRows->flatten(1)->count();

        $summaryCards = [
            'plan_today' => $planTodayCount,
            'realization_pending' => $realizationPending,
            'manager_findings' => $managerFindingsCount,
            'stagnant_roadmap' => 0,
        ];

        $activeBigRocks = \App\Models\BigRock::query()
            ->where('user_id', $hod->id)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'title'])
            ->map(function ($br) use ($periodFrom, $periodTo) {
                // Samakan definisi progress dengan halaman Big Rock:
                // progress dihitung dari status RoadmapItem (planned/in_progress/blocked/finished).
                $weights = [
                    'planned' => 0.0,
                    'in_progress' => 0.6,
                    'blocked' => 0.3,
                    'finished' => 1.0,
                    'completed' => 1.0,
                    'done' => 1.0,
                ];

                $roadmapStatuses = \App\Models\RoadmapItem::query()
                    ->where('big_rock_id', $br->id)
                    ->where('status', '!=', 'archived')
                    ->pluck('status');

                $roadmapCount = $roadmapStatuses->count();
                $score = $roadmapStatuses->map(fn ($s) => (float) ($weights[$s] ?? 0.0))->sum();
                $progress = $roadmapCount > 0 ? (int) round(($score / $roadmapCount) * 100) : 0;

                // UX: kalau semua masih planned / belum ada score sama sekali.
                if ($roadmapCount === 0 || $score <= 0.0) {
                    $status = 'not_started';
                } else {
                    $status = $progress >= 70 ? 'on_track' : ($progress >= 40 ? 'at_risk' : 'blocked');
                }

                return [
                    'title' => $br->title,
                    'roadmap_count' => $roadmapCount,
                    'progress' => $progress,
                    'status' => $status,
                ];
            })
            ->all();

        $managersNeedingAttention = $divisionManagers
            ->map(function ($mgr) use ($managerFindingRows) {
                $rows = $managerFindingRows->get($mgr->id, collect());
                $findings = $rows->count();

                $maxSeverity = $rows->contains('severity', 'high') ? 'major' : ($rows->contains('severity', 'medium') ? 'medium' : 'minor');
                $latest = $rows->sortByDesc('finding_date')->sortByDesc('id')->first();

                return [
                    'name' => $mgr->name,
                    'findings' => $findings,
                    'severity' => $maxSeverity,
                    'latest' => $latest['title'] ?? '—',
                ];
            })
            ->sortByDesc('findings')
            ->take(6)
            ->values()
            ->all();
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
                        <p class="text-sm text-primary/70">Window Plan: {{ $planWindowInfo }}</p>
                    </div>
                </div>
                <a href="{{ route('hod.daily-entry') }}" class="btn-primary shrink-0">Isi Plan Sekarang</a>
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
                        <p class="text-sm text-warning/70">Window Realisasi: {{ $realizationWindowInfo }}</p>
                    </div>
                </div>
                <a href="{{ route('hod.daily-entry') }}" class="btn-primary shrink-0">Isi Realisasi</a>
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
                    <p class="text-sm text-muted mt-1">{{ $br['roadmap_count'] }} roadmap items</p>
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm text-muted mb-1">
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
                        <p class="text-sm text-muted mt-0.5">{{ $mgr['latest'] }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui.severity-badge :severity="$mgr['severity']" />
                        <span class="text-sm text-muted">{{ $mgr['findings'] }}x</span>
                    </div>
                </div>
            @endforeach
        </x-ui.card>
    </div>

    {{-- Quick Actions --}}
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('hod.history') }}" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Riwayat Entry
        </a>
        <a href="{{ route('hod.big-rock') }}" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Big Rock
        </a>
        <a href="{{ route('hod.division-entries') }}" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Lihat Entry Divisi
        </a>
        <a href="{{ route('hod.ai-chat') }}" class="btn-secondary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            AI Chat
        </a>
    </div>
</x-layouts.app>
