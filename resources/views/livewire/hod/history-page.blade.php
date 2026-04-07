{{--
    HoD History Page
    Route: /hod/history
    Component: App\Livewire\Hod\HistoryPage
--}}

<x-layouts.app title="Riwayat Entry">
    @php
        $historyByDate = [
            '7 Jul 2025 (Senin)' => [
                ['id' => 1, 'title' => 'Review dokumen procurement', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Implementasi SOP', 'plan_status' => 'submitted', 'realization_status' => 'missing', 'severity' => null],
                ['id' => 2, 'title' => 'Koordinasi tim lapangan', 'big_rock' => 'Pengembangan SDM', 'roadmap' => 'Training Tim', 'plan_status' => 'submitted', 'realization_status' => 'finished', 'severity' => null],
            ],
            '4 Jul 2025 (Jumat)' => [
                ['id' => 3, 'title' => 'Meeting komite SOP', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Audit Proses', 'plan_status' => 'submitted', 'realization_status' => 'in_progress', 'severity' => 'medium'],
            ],
            '3 Jul 2025 (Kamis)' => [
                ['id' => 4, 'title' => 'Finalisasi budget Q3', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Implementasi SOP', 'plan_status' => 'late', 'realization_status' => 'finished', 'severity' => 'minor'],
                ['id' => 5, 'title' => 'Review performa tim', 'big_rock' => 'Pengembangan SDM', 'roadmap' => 'Evaluasi Kompetensi', 'plan_status' => 'submitted', 'realization_status' => 'not_finished', 'severity' => 'major'],
            ],
        ];
    @endphp

    <x-ui.page-header title="Riwayat Entry" description="Lihat entry plan dan realisasi sebelumnya" />

    {{-- Date range filter --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <input type="date" class="input w-40" value="2025-07-01" />
        <span class="text-muted self-center">—</span>
        <input type="date" class="input w-40" value="2025-07-07" />
    </div>

    <div x-data="{ drawerOpen: false, selectedEntry: null }">
        {{-- Timeline --}}
        <div class="space-y-6">
            @foreach($historyByDate as $date => $entries)
                <div>
                    {{-- Date header --}}
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-3 h-3 rounded-full bg-primary shrink-0"></div>
                        <h3 class="text-sm font-semibold text-text">{{ $date }}</h3>
                        <div class="flex-1 h-px bg-border"></div>
                    </div>

                    {{-- Entries for this date --}}
                    <div class="ml-6 space-y-2">
                        @foreach($entries as $entry)
                            <x-ui.card class="cursor-pointer hover:border-primary/30 transition-colors" @click="drawerOpen = true; selectedEntry = {{ json_encode($entry) }}">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-text">{{ $entry['title'] }}</p>
                                    @if($entry['severity'])
                                        <x-ui.severity-badge :severity="$entry['severity']" />
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-xs mt-1.5">
                                    <span class="badge-primary">{{ $entry['big_rock'] }}</span>
                                    <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    <span class="badge-muted">{{ $entry['roadmap'] }}</span>
                                </div>
                                <div class="flex gap-3 mt-2 text-xs">
                                    <span class="text-muted">Plan: </span><x-ui.status-badge :status="$entry['plan_status']" />
                                    <span class="text-muted ml-2">Real: </span><x-ui.status-badge :status="$entry['realization_status']" />
                                </div>
                            </x-ui.card>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Detail Drawer --}}
        <div x-show="drawerOpen" class="fixed inset-0 z-40 flex justify-end" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="drawerOpen = false"></div>
            <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0">
                <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface z-10">
                    <h3 class="font-semibold text-text">Detail Entry</h3>
                    <button @click="drawerOpen = false" class="text-muted hover:text-text">✕</button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <p class="text-base font-semibold text-text" x-text="selectedEntry?.title"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-muted">Big Rock</p><p class="text-sm text-text" x-text="selectedEntry?.big_rock"></p></div>
                        <div><p class="text-xs text-muted">Roadmap</p><p class="text-sm text-text" x-text="selectedEntry?.roadmap"></p></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-muted">Plan Status</p><p class="text-sm text-text" x-text="selectedEntry?.plan_status"></p></div>
                        <div><p class="text-xs text-muted">Realisasi Status</p><p class="text-sm text-text" x-text="selectedEntry?.realization_status"></p></div>
                    </div>
                    <template x-if="selectedEntry?.severity">
                        <div><p class="text-xs text-muted">Severity</p><p class="text-sm font-medium text-danger" x-text="selectedEntry?.severity?.toUpperCase()"></p></div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
