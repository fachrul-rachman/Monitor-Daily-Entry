{{--
    Director Divisions Page
    Route: /director/divisions
    Component: App\Livewire\Director\DivisionsPage
--}}

<x-layouts.app title="Divisi">
    @php
        $divisions = [
            ['id' => 1, 'name' => 'Operasional'],
            ['id' => 2, 'name' => 'Keuangan'],
            ['id' => 3, 'name' => 'IT'],
            ['id' => 4, 'name' => 'Marketing'],
        ];
        $selectedDivision = 1;
        $summaryCards = [
            'health_score' => 42,
            'total_findings' => 8,
            'missing_entries' => 3,
            'stagnant_roadmap' => 2,
        ];
        $peopleWithFindings = [
            ['name' => 'Budi Santoso', 'role' => 'Manager', 'findings' => 4, 'severity' => 'major'],
            ['name' => 'Rudi Hermawan', 'role' => 'Manager', 'findings' => 2, 'severity' => 'medium'],
            ['name' => 'Eko Prasetyo', 'role' => 'Manager', 'findings' => 1, 'severity' => 'minor'],
            ['name' => 'Dian Sari', 'role' => 'Manager', 'findings' => 1, 'severity' => 'minor'],
        ];
        $latestMajorFindings = [
            ['id' => 1, 'title' => 'Missing plan 3 hari berturut', 'user' => 'Budi Santoso', 'severity' => 'major', 'rule' => 'RULE-001: Missing submission > 2 hari', 'date' => '7 Jul 2025'],
            ['id' => 2, 'title' => 'Realisasi blocked tanpa eskalasi', 'user' => 'Budi Santoso', 'severity' => 'major', 'rule' => 'RULE-005: Blocked tanpa eskalasi > 1 hari', 'date' => '6 Jul 2025'],
            ['id' => 3, 'title' => 'Late submission berulang', 'user' => 'Rudi Hermawan', 'severity' => 'medium', 'rule' => 'RULE-003: Late > 3x per minggu', 'date' => '5 Jul 2025'],
        ];
        $stagnantRoadmapItems = [
            ['title' => 'Implementasi SOP Baru', 'big_rock' => 'Optimasi Proses', 'days_stagnant' => 14, 'status' => 'planned'],
            ['title' => 'Training Tim Lapangan', 'big_rock' => 'Pengembangan SDM', 'days_stagnant' => 21, 'status' => 'in_progress'],
        ];
    @endphp

    <x-ui.page-header title="Divisi" description="Analisis detail per divisi" />

    {{-- Division selector + date range --}}
    <div class="flex flex-wrap gap-3 mb-6">
        {{-- TODO: wire:model.live="selectedDivision" --}}
        <select class="input w-48">
            @foreach($divisions as $div)
                <option value="{{ $div['id'] }}" {{ $div['id'] === $selectedDivision ? 'selected' : '' }}>{{ $div['name'] }}</option>
            @endforeach
        </select>
        <input type="date" class="input w-40" value="2025-07-01" />
        <span class="text-muted self-center">—</span>
        <input type="date" class="input w-40" value="2025-07-07" />
    </div>

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
                <p class="text-sm text-muted">📊 Exception Trend</p>
            </div>
        </x-ui.card>
        <x-ui.card class="hidden lg:block">
            <h3 class="text-sm font-semibold text-text mb-4">Compliance Rate</h3>
            <div id="chart-div-compliance" class="h-[280px] flex items-center justify-center bg-app-bg rounded-lg">
                <p class="text-sm text-muted">📊 Compliance Chart</p>
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
                            <p class="text-xs text-muted">{{ $person['role'] }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-ui.severity-badge :severity="$person['severity']" />
                            <span class="text-xs text-muted">{{ $person['findings'] }}x</span>
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
                        <p class="text-xs text-muted mt-1">{{ $finding['user'] }} · {{ $finding['date'] }}</p>
                        <p class="text-xs text-primary mt-0.5">{{ $finding['rule'] }}</p>
                    </div>
                @endforeach
            </x-ui.card>

            {{-- Stagnant Roadmap --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-text mb-4">Roadmap Tidak Bergerak</h3>
                @foreach($stagnantRoadmapItems as $item)
                    <div class="py-2.5 {{ !$loop->last ? 'border-b border-border' : '' }}">
                        <p class="text-sm font-medium text-text">{{ $item['title'] }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ $item['big_rock'] }}</p>
                        <div class="flex items-center gap-2 mt-1.5">
                            <x-ui.status-badge :status="$item['status']" />
                            <span class="text-xs text-danger font-medium">{{ $item['days_stagnant'] }} hari tanpa aktivitas</span>
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
                            <p class="text-xs font-semibold text-primary uppercase tracking-wide">Big Rock</p>
                            <p class="text-sm font-medium text-text mt-1">Optimasi Proses</p>
                        </div>
                        <div class="flex justify-center"><svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide">Roadmap</p>
                            <p class="text-sm font-medium text-text mt-1">Implementasi SOP Baru</p>
                            <x-ui.status-badge status="in_progress" class="mt-1" />
                        </div>
                        <div class="flex justify-center"><svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide">Plan</p>
                            <p class="text-sm font-medium text-text mt-1">Review dokumen procurement</p>
                            <p class="text-xs text-muted mt-1">Submitted: 7 Jul 2025, 08:30</p>
                        </div>
                        <div class="flex justify-center"><svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide">Realization</p>
                            <p class="text-sm font-medium text-text mt-1">Belum diisi</p>
                            <x-ui.status-badge status="missing" class="mt-1" />
                        </div>

                        {{-- Triggered Rules --}}
                        <div class="mt-4 pt-4 border-t border-border">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Triggered Rules</p>
                            <div class="space-y-2">
                                <div class="flex items-start gap-2 bg-danger-bg rounded-lg p-3">
                                    <x-ui.severity-badge severity="major" />
                                    <p class="text-sm text-text">Missing submission > 2 hari berturut-turut</p>
                                </div>
                            </div>
                        </div>

                        {{-- Timestamps --}}
                        <div class="mt-4 pt-4 border-t border-border">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">Timestamps</p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div><p class="text-xs text-muted">Submitted</p><p class="text-text">7 Jul 2025, 08:30</p></div>
                                <div><p class="text-xs text-muted">Window</p><p class="text-text">08:00 – 17:00</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
