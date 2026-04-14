{{--
    Manager Dashboard Page
    Route: /manager/dashboard
    Component: App\Livewire\Manager\DashboardPage
    Lebih simpel dari HoD — tidak ada section divisi. Satu fokus: isi harian.
--}}

<x-layouts.app title="Dashboard">
    @php
        $user = auth()->user();
        $today = \Illuminate\Support\Carbon::today();
        $todayDate = $today->translatedFormat('l, j F Y');

        $setting = \App\Models\ReportSetting::current();
        $planWindowInfo = sprintf(
            '%s - %s',
            \Illuminate\Support\Carbon::parse($setting->plan_open_time)->format('H:i'),
            \Illuminate\Support\Carbon::parse($setting->plan_close_time)->format('H:i'),
        );
        $realizationWindowInfo = sprintf(
            '%s - %s',
            \Illuminate\Support\Carbon::parse($setting->realization_open_time)->format('H:i'),
            \Illuminate\Support\Carbon::parse($setting->realization_close_time)->format('H:i'),
        );

        $todayEntry = \App\Models\DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', $today->toDateString())
            ->first();

        $planItemsToday = 0;
        $pendingRealization = 0;

        if ($todayEntry) {
            $planItemsToday = \App\Models\DailyEntryItem::query()
                ->where('daily_entry_id', $todayEntry->id)
                ->whereNotNull('plan_title')
                ->count();

            // Pending = item yang sudah punya plan, tapi realisasi masih draft/belum ada.
            $pendingRealization = \App\Models\DailyEntryItem::query()
                ->where('daily_entry_id', $todayEntry->id)
                ->whereNotNull('plan_title')
                ->where(function ($q) {
                    $q->whereNull('realization_status')
                        ->orWhere('realization_status', 'draft');
                })
                ->count();
        }

        // Status banner mengikuti "status pelaporan" (daily_entries), bukan status draft di item.
        $planFilled = (bool) ($todayEntry && in_array($todayEntry->plan_status, ['submitted', 'late'], true));
        $realizationFilled = (bool) ($todayEntry && in_array($todayEntry->realization_status, ['submitted', 'late'], true));

        $activeBigRockCount = \App\Models\BigRock::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        $summaryCards = [
            'plan_today' => ['value' => $planItemsToday, 'label' => $planFilled ? 'Sudah diisi' : 'Belum diisi'],
            'realization_today' => ['value' => $pendingRealization, 'label' => $realizationFilled ? 'Sudah diisi' : 'Belum diisi'],
            'big_rock_active' => ['value' => $activeBigRockCount, 'label' => 'Aktif'],
        ];

        $activeRoadmapItems = \App\Models\RoadmapItem::query()
            ->with(['bigRock:id,title'])
            ->whereIn('big_rock_id', \App\Models\BigRock::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->select('id'))
            ->orderBy('sort_order')
            ->limit(5)
            ->get()
            ->map(fn ($ri) => [
                'title' => $ri->title,
                'big_rock' => $ri->bigRock?->title ?? '—',
                'roadmap' => $ri->title,
                'status' => $ri->status,
            ])
            ->all();

        $recentEntries = \App\Models\DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', '<=', $today->toDateString())
            ->orderByDesc('entry_date')
            ->limit(7)
            ->get(['id', 'entry_date', 'plan_status', 'realization_status']);

        $entryIds = $recentEntries->pluck('id')->all();
        $itemsByEntry = \App\Models\DailyEntryItem::query()
            ->whereIn('daily_entry_id', $entryIds)
            ->orderBy('id')
            ->get(['daily_entry_id', 'plan_title', 'realization_status'])
            ->groupBy('daily_entry_id');

        $recentHistory = $recentEntries->map(function ($e) use ($itemsByEntry) {
            $items = $itemsByEntry[$e->id] ?? collect();
            $planCount = $items->whereNotNull('plan_title')->count();
            $realCount = $items->whereNotNull('plan_title')->where('realization_status', '!=', 'draft')->whereNotNull('realization_status')->count();
            $title = $items->first()?->plan_title ?? '—';

            $planStatus = $planCount === 0 ? 'missing' : ($e->plan_status ?: 'submitted');
            $realStatus = $planCount === 0 ? 'missing' : (($realCount >= $planCount) ? ($e->realization_status ?: 'submitted') : 'missing');

            return [
                'title' => $title,
                'date' => \Illuminate\Support\Carbon::parse($e->entry_date)->translatedFormat('j M'),
                'plan' => $planStatus,
                'real' => $realStatus,
            ];
        })->all();
    @endphp

    <x-ui.page-header title="Dashboard" :description="$todayDate" />

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
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
                        <p class="text-sm text-primary/70">Window Plan: {{ $planWindowInfo }}. Segera isi plan harian Anda.</p>
                    </div>
                </div>
                <a href="{{ route('manager.daily-entry') }}" class="btn-primary shrink-0 text-base px-6 py-3">Isi Plan Sekarang</a>
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
                        <p class="text-sm text-warning/70">Window Realisasi: {{ $realizationWindowInfo }}. Isi realisasi sebelum tutup.</p>
                    </div>
                </div>
                <a href="{{ route('manager.daily-entry') }}" class="btn-primary shrink-0 text-base px-6 py-3">Isi Realisasi</a>
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
            @forelse($activeRoadmapItems as $item)
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
            @empty
                <x-ui.empty-state title="Belum ada roadmap aktif" icon="document" class="py-8" />
            @endforelse
        </div>
    </div>

    {{-- Recent History Timeline --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-text mb-3">Riwayat Terbaru</h3>
        <div class="space-y-2">
            @forelse($recentHistory as $entry)
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
            @empty
                <x-ui.empty-state title="Belum ada riwayat" icon="calendar" class="py-8" />
            @endforelse
        </div>
        <a href="{{ route('manager.history') }}" class="text-sm text-primary font-medium mt-3 inline-block hover:underline">Lihat Semua Riwayat &rarr;</a>
    </div>
</x-layouts.app>
