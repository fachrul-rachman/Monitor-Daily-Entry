{{--
    HoD Big Rock Page
    Route: /hod/big-rock
    Component: App\Livewire\Hod\BigRockPage
--}}

<x-layouts.app title="Big Rock">
    @php
        $bigRocks = [
            ['id' => 1, 'title' => 'Optimasi Proses Operasional Q3', 'description' => 'Meningkatkan efisiensi operasional melalui perbaikan SOP dan sistem.', 'start' => '1 Jul 2025', 'end' => '30 Sep 2025', 'status' => 'active', 'roadmap_count' => 4, 'progress' => 60],
            ['id' => 2, 'title' => 'Pengembangan SDM Tim', 'description' => 'Program pelatihan dan evaluasi kompetensi seluruh tim divisi.', 'start' => '15 Jun 2025', 'end' => '30 Aug 2025', 'status' => 'active', 'roadmap_count' => 3, 'progress' => 35],
            ['id' => 3, 'title' => 'Digitalisasi Laporan', 'description' => 'Migrasi laporan manual ke sistem digital terintegrasi.', 'start' => '1 Apr 2025', 'end' => '30 Jun 2025', 'status' => 'completed', 'roadmap_count' => 2, 'progress' => 100],
        ];
    @endphp

    <x-ui.page-header title="Big Rock" description="Kelola big rock dan roadmap item">
        <x-slot:actions>
            {{-- TODO: wire:click="openCreate" / dispatch event --}}
            <button class="btn-primary gap-2" x-data @click="$dispatch('open-big-rock-form')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Big Rock
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <div x-data="{ roadmapOpen: false, selectedBigRock: null }">
        {{-- Big Rock Card List --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($bigRocks as $br)
                <x-ui.card class="flex flex-col">
                    <div class="flex items-start justify-between mb-2">
                        <x-ui.status-badge :status="$br['status']" />
                        <div class="flex items-center gap-1" x-data="{ menuOpen: false }">
                            <div class="relative">
                                <button @click.stop="menuOpen = !menuOpen" class="p-1 rounded hover:bg-app-bg text-muted hover:text-text">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                                </button>
                                <div x-show="menuOpen" @click.away="menuOpen = false" class="absolute right-0 mt-1 w-36 bg-surface border border-border rounded-lg shadow-lg z-10 py-1" style="display:none;">
                                    <button class="w-full text-left px-3 py-2 text-sm text-text hover:bg-app-bg">Edit</button>
                                    <button class="w-full text-left px-3 py-2 text-sm text-danger hover:bg-app-bg">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 class="text-sm font-semibold text-text mb-1">{{ $br['title'] }}</h4>
                    <p class="text-sm text-muted line-clamp-2 mb-3">{{ $br['description'] }}</p>

                    <div class="text-sm text-muted mb-3">
                        {{ $br['start'] }} — {{ $br['end'] }}
                    </div>

                    {{-- Progress --}}
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-sm text-muted mb-1">
                            <span>Progress</span>
                            <span>{{ $br['progress'] }}%</span>
                        </div>
                        <div class="w-full h-2 bg-app-bg rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all {{ $br['progress'] >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $br['progress'] }}%"></div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="mt-auto pt-3 border-t border-border flex items-center justify-between">
                        <span class="text-sm text-muted">{{ $br['roadmap_count'] }} roadmap items</span>
                        <button
                            @click="roadmapOpen = true; selectedBigRock = {{ json_encode($br) }}"
                            class="text-sm text-primary font-medium hover:underline"
                        >Kelola Roadmap →</button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        {{-- Roadmap Manager Panel (inline reference) --}}
        {{-- TODO: @livewire('hod.roadmap-manager-panel') --}}
        <div x-show="roadmapOpen" class="fixed inset-0 z-40 flex justify-end" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="roadmapOpen = false"></div>
            <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0">
                <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface z-10">
                    <div>
                        <h3 class="font-semibold text-text">Roadmap Items</h3>
                        <p class="text-sm text-muted" x-text="selectedBigRock?.title"></p>
                    </div>
                    <button @click="roadmapOpen = false" class="text-muted hover:text-text">✕</button>
                </div>
                <div class="p-5">
                    {{-- Dummy roadmap items --}}
                    @php
                        $dummyRoadmaps = [
                            ['id' => 1, 'title' => 'Implementasi SOP Baru', 'status' => 'in_progress'],
                            ['id' => 2, 'title' => 'Audit Proses Existing', 'status' => 'planned'],
                            ['id' => 3, 'title' => 'Sosialisasi ke Tim', 'status' => 'not_started'],
                            ['id' => 4, 'title' => 'Evaluasi & Perbaikan', 'status' => 'not_started'],
                        ];
                    @endphp
                    <div class="space-y-2 mb-6">
                        @foreach($dummyRoadmaps as $idx => $rm)
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-app-bg transition-colors">
                                <span class="text-xs font-mono text-muted w-5 text-center shrink-0">{{ $idx + 1 }}</span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-text">{{ $rm['title'] }}</p>
                                </div>
                                <x-ui.status-badge :status="$rm['status']" />
                                <div class="flex gap-1 shrink-0">
                                    <button class="p-1 text-muted hover:text-primary"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                    <button class="p-1 text-muted hover:text-danger"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Inline add form --}}
                    <div class="border-t border-border pt-4">
                        <h4 class="text-sm font-semibold text-text mb-3">Tambah Roadmap Item</h4>
                        <form class="space-y-3">
                            <input type="text" class="input" placeholder="Judul roadmap item..." />
                            <select class="input">
                                <option value="not_started">Not Started</option>
                                <option value="planned">Planned</option>
                                <option value="in_progress">In Progress</option>
                            </select>
                            <button type="button" class="btn-primary w-full">Tambah Item</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
