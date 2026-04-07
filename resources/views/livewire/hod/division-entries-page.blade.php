{{--
    HoD Division Entries Page
    Route: /hod/division-entries
    Component: App\Livewire\Hod\DivisionEntriesPage
--}}

<x-layouts.app title="Entry Divisi">
    @php
        $entries = [
            ['id' => 1, 'user' => 'Budi Santoso', 'role' => 'Manager', 'title' => 'Review dokumen procurement', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Implementasi SOP', 'plan_status' => 'submitted', 'realization_status' => 'finished', 'date' => '7 Jul 2025', 'has_finding' => false, 'severity' => null],
            ['id' => 2, 'user' => 'Budi Santoso', 'role' => 'Manager', 'title' => 'Koordinasi vendor baru', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Audit Proses', 'plan_status' => 'submitted', 'realization_status' => 'missing', 'date' => '7 Jul 2025', 'has_finding' => true, 'severity' => 'major', 'finding_rule' => 'Missing realization > 1 hari'],
            ['id' => 3, 'user' => 'Rudi Hermawan', 'role' => 'Manager', 'title' => 'Training modul baru', 'big_rock' => 'Pengembangan SDM', 'roadmap' => 'Training Tim', 'plan_status' => 'late', 'realization_status' => 'in_progress', 'date' => '7 Jul 2025', 'has_finding' => true, 'severity' => 'medium', 'finding_rule' => 'Late submission > 3x seminggu'],
            ['id' => 4, 'user' => 'Eko Prasetyo', 'role' => 'Manager', 'title' => 'Persiapan laporan bulanan', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Implementasi SOP', 'plan_status' => 'submitted', 'realization_status' => 'finished', 'date' => '7 Jul 2025', 'has_finding' => false, 'severity' => null],
            ['id' => 5, 'user' => 'Dian Sari', 'role' => 'Manager', 'title' => 'Follow up customer complaint', 'big_rock' => 'Optimasi Proses', 'roadmap' => 'Audit Proses', 'plan_status' => 'submitted', 'realization_status' => 'finished', 'date' => '7 Jul 2025', 'has_finding' => false, 'severity' => null],
        ];
    @endphp

    <x-ui.page-header title="Entry Divisi" description="Lihat entry plan dan realisasi seluruh anggota divisi" />

    {{-- Filter bar --}}
    <div class="flex flex-wrap gap-3 mb-4 items-center" x-data="{ findingsOnly: false }">
        <input type="date" class="input w-40" value="2025-07-07" />
        <select class="input w-40">
            <option value="">Semua User</option>
            <option>Budi Santoso</option>
            <option>Rudi Hermawan</option>
            <option>Eko Prasetyo</option>
            <option>Dian Sari</option>
        </select>

        {{-- Toggle: Hanya Tampilkan Temuan --}}
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer min-h-[44px] transition-colors"
            :class="findingsOnly ? 'border-danger bg-danger-bg text-danger' : 'border-border bg-surface text-text'"
        >
            {{-- TODO: wire:model.live="findingsOnly" --}}
            <input type="checkbox" x-model="findingsOnly" class="w-4 h-4 rounded accent-danger border-border">
            <span class="text-sm font-medium">Hanya Temuan</span>
        </label>
    </div>

    <div x-data="{ drawerOpen: false, selectedEntry: null, expandedId: null }">
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-app-bg border-b border-border">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">User</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Judul Plan</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Big Rock</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Plan</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Realisasi</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Temuan</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($entries as $entry)
                            <tr class="hover:bg-app-bg transition-colors {{ $entry['has_finding'] ? 'bg-danger-bg/30' : '' }}">
                                <td class="px-4 py-3.5 font-medium text-text">{{ $entry['user'] }}</td>
                                <td class="px-4 py-3.5 text-text max-w-[200px] truncate">{{ $entry['title'] }}</td>
                                <td class="px-4 py-3.5"><span class="badge-primary">{{ $entry['big_rock'] }}</span></td>
                                <td class="px-4 py-3.5"><x-ui.status-badge :status="$entry['plan_status']" /></td>
                                <td class="px-4 py-3.5"><x-ui.status-badge :status="$entry['realization_status']" /></td>
                                <td class="px-4 py-3.5">
                                    @if($entry['has_finding'])
                                        <x-ui.severity-badge :severity="$entry['severity']" />
                                    @else
                                        <span class="text-xs text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <button
                                        @click="drawerOpen = true; selectedEntry = {{ json_encode($entry) }}"
                                        class="text-sm text-primary font-medium hover:underline"
                                    >Detail</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Accordion Cards --}}
        <div class="block md:hidden space-y-3">
            @foreach($entries as $entry)
                <x-ui.card class="{{ $entry['has_finding'] ? 'border-l-4 border-l-danger' : '' }}">
                    <div class="flex items-start justify-between cursor-pointer" @click="expandedId = expandedId === {{ $entry['id'] }} ? null : {{ $entry['id'] }}">
                        <div class="flex-1">
                            <p class="font-semibold text-text text-sm">{{ $entry['user'] }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ $entry['title'] }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($entry['has_finding'])
                                <x-ui.severity-badge :severity="$entry['severity']" />
                            @endif
                            <svg class="w-4 h-4 text-muted transition-transform" :class="expandedId === {{ $entry['id'] }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    {{-- Expanded details --}}
                    <div x-show="expandedId === {{ $entry['id'] }}" x-transition style="display:none;" class="mt-3 pt-3 border-t border-border space-y-2">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="badge-primary">{{ $entry['big_rock'] }}</span>
                            <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="badge-muted">{{ $entry['roadmap'] }}</span>
                        </div>
                        <div class="flex gap-3 text-xs">
                            <span class="text-muted">Plan:</span> <x-ui.status-badge :status="$entry['plan_status']" />
                            <span class="text-muted ml-2">Real:</span> <x-ui.status-badge :status="$entry['realization_status']" />
                        </div>
                        @if($entry['has_finding'])
                            <div class="bg-danger-bg rounded-lg p-2 text-xs text-danger">
                                ⚠ {{ $entry['finding_rule'] ?? 'Temuan terdeteksi' }}
                            </div>
                        @endif
                        <button
                            @click.stop="drawerOpen = true; selectedEntry = {{ json_encode($entry) }}"
                            class="text-sm text-primary font-medium"
                        >Lihat Full Detail →</button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        {{-- Detail Drawer with Full Chain --}}
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
                    {{-- User info --}}
                    <div>
                        <p class="text-base font-semibold text-text" x-text="selectedEntry?.title"></p>
                        <p class="text-sm text-muted mt-0.5" x-text="selectedEntry?.user + ' · ' + selectedEntry?.date"></p>
                    </div>

                    {{-- Hierarchy chain --}}
                    <div>
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Hierarki</p>
                        <div class="bg-primary-light border border-primary/20 rounded-lg p-3 mb-2">
                            <p class="text-xs font-semibold text-primary uppercase">Big Rock</p>
                            <p class="text-sm font-medium text-text mt-0.5" x-text="selectedEntry?.big_rock"></p>
                        </div>
                        <div class="flex justify-center py-0.5"><svg class="w-4 h-4 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3 mb-2">
                            <p class="text-xs font-semibold text-muted uppercase">Roadmap</p>
                            <p class="text-sm font-medium text-text mt-0.5" x-text="selectedEntry?.roadmap"></p>
                        </div>
                        <div class="flex justify-center py-0.5"><svg class="w-4 h-4 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3 mb-2">
                            <p class="text-xs font-semibold text-muted uppercase">Plan</p>
                            <p class="text-sm font-medium text-text mt-0.5" x-text="selectedEntry?.title"></p>
                        </div>
                        <div class="flex justify-center py-0.5"><svg class="w-4 h-4 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>
                        <div class="bg-app-bg border border-border rounded-lg p-3">
                            <p class="text-xs font-semibold text-muted uppercase">Realization</p>
                            <p class="text-sm font-medium text-text mt-0.5" x-text="selectedEntry?.realization_status === 'missing' ? 'Belum diisi' : 'Sudah diisi'"></p>
                        </div>
                    </div>

                    {{-- Finding --}}
                    <template x-if="selectedEntry?.has_finding">
                        <div class="bg-danger-bg border border-danger/20 rounded-lg p-3">
                            <p class="text-xs font-semibold text-danger uppercase mb-1">Temuan Terdeteksi</p>
                            <p class="text-sm text-danger" x-text="selectedEntry?.finding_rule || 'Exception rule triggered'"></p>
                        </div>
                    </template>

                    {{-- Statuses --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-muted">Plan Status</p><p class="text-sm font-medium text-text" x-text="selectedEntry?.plan_status"></p></div>
                        <div><p class="text-xs text-muted">Realisasi Status</p><p class="text-sm font-medium text-text" x-text="selectedEntry?.realization_status"></p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
