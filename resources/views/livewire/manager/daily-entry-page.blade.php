{{--
    Manager Daily Entry Page
    Route: /manager/daily-entry
    Component: App\Livewire\Manager\DailyEntryPage

    Layout identik dengan HoD untuk meminimalkan training.
    Perbedaan hanya di scope data (personal manager, bukan divisi).
    Semua komponen, logika UI, dan interaksi sama persis.
--}}

<x-layouts.app title="Entry Harian">
    @php
        $todayDate = 'Senin, 7 Juli 2025';
        $planWindowOpen = '08:00';
        $planWindowClose = '17:00';
        $realizationWindowOpen = '15:00';
        $realizationWindowClose = '23:59';
        $isPlanWindowOpen = true;
        $isRealizationWindowOpen = false;

        $bigRocks = [
            ['id' => 1, 'title' => 'Optimasi Proses Operasional Q3'],
            ['id' => 2, 'title' => 'Pengembangan SDM Tim'],
        ];
        $roadmapsByBigRock = [
            1 => [
                ['id' => 1, 'title' => 'Implementasi SOP Baru'],
                ['id' => 2, 'title' => 'Audit Proses Existing'],
            ],
            2 => [
                ['id' => 3, 'title' => 'Training Tim Lapangan'],
                ['id' => 4, 'title' => 'Evaluasi Kompetensi'],
            ],
        ];

        $existingPlans = [
            ['id' => 1, 'title' => 'Review dokumen procurement', 'description' => 'Mereview dokumen procurement vendor baru Q3 untuk memastikan compliance.', 'big_rock' => 'Optimasi Proses Operasional Q3', 'roadmap' => 'Implementasi SOP Baru', 'planned_hours' => 3, 'status' => 'submitted'],
            ['id' => 2, 'title' => 'Koordinasi tim lapangan', 'description' => 'Meeting dengan supervisor lapangan terkait SOP baru.', 'big_rock' => 'Pengembangan SDM Tim', 'roadmap' => 'Training Tim Lapangan', 'planned_hours' => 2, 'status' => 'submitted'],
        ];
    @endphp

    <x-ui.page-header title="Entry Harian" :description="$todayDate" />

    {{-- Window Status Bar --}}
    <div class="mb-6 flex flex-wrap gap-3">
        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ $isPlanWindowOpen ? 'bg-success-bg text-success' : 'bg-app-bg text-muted' }}">
            <span class="w-2 h-2 rounded-full {{ $isPlanWindowOpen ? 'bg-success' : 'bg-muted' }}"></span>
            Plan: {{ $planWindowOpen }} – {{ $planWindowClose }}
            <span class="font-semibold">{{ $isPlanWindowOpen ? 'Terbuka' : 'Tertutup' }}</span>
        </div>
        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ $isRealizationWindowOpen ? 'bg-success-bg text-success' : 'bg-app-bg text-muted' }}">
            <span class="w-2 h-2 rounded-full {{ $isRealizationWindowOpen ? 'bg-success' : 'bg-muted' }}"></span>
            Realisasi: {{ $realizationWindowOpen }} – {{ $realizationWindowClose }}
            <span class="font-semibold">{{ $isRealizationWindowOpen ? 'Terbuka' : 'Tertutup' }}</span>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'plan' }">
        {{-- Tab buttons — min 44px touch --}}
        <div class="flex border-b border-border mb-6">
            <button
                @click="tab = 'plan'"
                :class="tab === 'plan' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted hover:text-text'"
                class="px-4 py-3 text-sm min-h-[44px] transition-colors"
            >Plan</button>
            <button
                @click="tab = 'realisasi'"
                :class="tab === 'realisasi' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted hover:text-text'"
                class="px-4 py-3 text-sm min-h-[44px] transition-colors"
            >Realisasi</button>
        </div>

        {{-- ==================== TAB: PLAN ==================== --}}
        <div x-show="tab === 'plan'" x-data="{ showAddForm: false, selectedBigRock: '' }">
            {{-- Existing plan items --}}
            <div class="space-y-3 mb-6">
                @forelse($existingPlans as $plan)
                    <x-ui.card>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-semibold text-text text-sm">{{ $plan['title'] }}</p>
                                <p class="text-xs text-muted mt-0.5 line-clamp-2">{{ $plan['description'] }}</p>
                            </div>
                            <x-ui.status-badge :status="$plan['status']" />
                        </div>
                        {{-- Hierarchy visual --}}
                        <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="badge-primary">{{ $plan['big_rock'] }}</span>
                            <svg class="w-3 h-3 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="badge-muted">{{ $plan['roadmap'] }}</span>
                        </div>
                        <div class="mt-2 text-xs text-muted">
                            Jam rencana: {{ $plan['planned_hours'] }} jam
                        </div>
                        @if($isPlanWindowOpen)
                            <div class="mt-3 pt-3 border-t border-border flex gap-2">
                                {{-- TODO: wire:click="editPlan({{ $plan['id'] }})" --}}
                                <button class="text-sm text-primary font-medium">Edit</button>
                                {{-- TODO: wire:click="removePlan({{ $plan['id'] }})" --}}
                                <button class="text-sm text-danger font-medium ml-auto">Hapus</button>
                            </div>
                        @endif
                    </x-ui.card>
                @empty
                    <x-ui.empty-state
                        title="Belum ada plan hari ini"
                        description="Tambah plan pertama untuk memulai hari kerja."
                        icon="clipboard"
                        cta-label="Tambah Plan"
                    />
                @endforelse
            </div>

            {{-- Add Plan Button --}}
            @if($isPlanWindowOpen)
                <button
                    @click="showAddForm = !showAddForm"
                    class="btn-secondary w-full md:w-auto gap-2 mb-6"
                    :class="showAddForm ? 'bg-app-bg' : ''"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span x-text="showAddForm ? 'Tutup Form' : 'Tambah Plan'">Tambah Plan</span>
                </button>
            @endif

            {{-- Add Plan Form --}}
            <div x-show="showAddForm" x-transition style="display:none;">
                <x-ui.card class="border-primary/30 border-2">
                    <h4 class="text-sm font-semibold text-text mb-4" style="font-family: 'DM Sans', sans-serif;">Plan Baru</h4>
                    {{-- TODO: wire:submit.prevent="addPlan" --}}
                    <form class="space-y-4">
                        {{-- Big Rock select --}}
                        <div>
                            <label class="label">Big Rock <span class="text-danger">*</span></label>
                            {{-- TODO: wire:model.live="selectedBigRock" --}}
                            <select class="input" x-model="selectedBigRock">
                                <option value="">Pilih Big Rock...</option>
                                @foreach($bigRocks as $br)
                                    <option value="{{ $br['id'] }}">{{ $br['title'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Roadmap select (dependent) --}}
                        <div>
                            <label class="label">Roadmap <span class="text-danger">*</span></label>
                            {{-- TODO: wire:model="selectedRoadmap" --}}
                            <select class="input">
                                <option value="">Pilih Roadmap...</option>
                                @foreach($roadmapsByBigRock as $brId => $roadmaps)
                                    @foreach($roadmaps as $rm)
                                        <option value="{{ $rm['id'] }}" x-show="selectedBigRock == '{{ $brId }}'">{{ $rm['title'] }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        {{-- Hierarchy visual preview --}}
                        <div class="bg-app-bg rounded-lg p-3">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">Hierarki</p>
                            <div class="flex items-center gap-1.5 text-xs">
                                <span class="badge-primary" x-text="selectedBigRock ? document.querySelector('[x-model=selectedBigRock] option:checked')?.textContent || 'Big Rock' : '—'">—</span>
                                <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <span class="badge-muted">Roadmap</span>
                                <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <span class="text-text font-medium">Plan ini</span>
                            </div>
                        </div>

                        <div>
                            <label class="label">Judul Plan <span class="text-danger">*</span></label>
                            <input type="text" class="input" placeholder="Apa yang akan dikerjakan hari ini?" />
                        </div>

                        <div>
                            <label class="label">Deskripsi</label>
                            <textarea class="input min-h-[80px]" rows="3" placeholder="Detail rencana kerja..."></textarea>
                        </div>

                        <div>
                            <label class="label">Alasan/Catatan</label>
                            <textarea class="input min-h-[60px]" rows="2" placeholder="Alasan atau catatan tambahan..."></textarea>
                        </div>

                        <div>
                            <label class="label">Jam Rencana <span class="text-danger">*</span></label>
                            <input type="number" class="input w-32" min="0.5" max="8" step="0.5" placeholder="2" />
                            <p class="text-xs text-muted mt-1">Estimasi waktu dalam jam (0.5 – 8)</p>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" @click="showAddForm = false" class="btn-secondary flex-1">Batal</button>
                            <button type="submit" class="btn-primary flex-1">Tambah Plan</button>
                        </div>
                    </form>
                </x-ui.card>
            </div>

            {{-- Submit Semua Plan (sticky bottom mobile) --}}
            @if(count($existingPlans) > 0 && $isPlanWindowOpen)
                <div class="fixed bottom-0 left-0 right-0 p-4 bg-surface border-t border-border md:static md:p-0 md:border-0 md:mt-6 md:bg-transparent z-30">
                    {{-- TODO: wire:click="submitAllPlans" --}}
                    <button class="btn-primary w-full md:w-auto">
                        Submit Semua Plan ({{ count($existingPlans) }} item)
                    </button>
                </div>
                <div class="h-20 md:hidden"></div>
            @endif
        </div>

        {{-- ==================== TAB: REALISASI ==================== --}}
        <div x-show="tab === 'realisasi'" x-data="{ expandedId: null }" style="display:none;">
            @if(!$isRealizationWindowOpen)
                <div class="bg-warning-bg border border-warning/20 rounded-xl p-4 mb-4 flex items-center gap-2 text-sm text-warning">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Window realisasi belum terbuka ({{ $realizationWindowOpen }} – {{ $realizationWindowClose }})
                </div>
            @endif

            <div class="space-y-3">
                @forelse($existingPlans as $plan)
                    <x-ui.card class="overflow-hidden">
                        <div class="flex items-start justify-between cursor-pointer" @click="expandedId = expandedId === {{ $plan['id'] }} ? null : {{ $plan['id'] }}">
                            <div class="flex-1">
                                <p class="font-semibold text-text text-sm">{{ $plan['title'] }}</p>
                                <div class="flex flex-wrap items-center gap-1.5 text-xs mt-1">
                                    <span class="badge-primary">{{ $plan['big_rock'] }}</span>
                                    <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    <span class="badge-muted">{{ $plan['roadmap'] }}</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-muted shrink-0 transition-transform" :class="expandedId === {{ $plan['id'] }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>

                        <div x-show="expandedId === {{ $plan['id'] }}" x-transition style="display:none;" class="mt-4 pt-4 border-t border-border">
                            <form class="space-y-4">
                                <div>
                                    <label class="label">Status Realisasi <span class="text-danger">*</span></label>
                                    <div class="space-y-2" x-data="{ status: '' }">
                                        <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg border border-border hover:bg-app-bg cursor-pointer min-h-[44px]"
                                            :class="status === 'finished' ? 'border-success bg-success-bg' : ''">
                                            <input type="radio" name="status_{{ $plan['id'] }}" value="finished" x-model="status" class="accent-success">
                                            <span class="text-sm text-text">Selesai</span>
                                        </label>
                                        <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg border border-border hover:bg-app-bg cursor-pointer min-h-[44px]"
                                            :class="status === 'in_progress' ? 'border-warning bg-warning-bg' : ''">
                                            <input type="radio" name="status_{{ $plan['id'] }}" value="in_progress" x-model="status" class="accent-warning">
                                            <span class="text-sm text-text">Sedang Berjalan</span>
                                        </label>
                                        <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg border border-border hover:bg-app-bg cursor-pointer min-h-[44px]"
                                            :class="status === 'not_finished' ? 'border-danger bg-danger-bg' : ''">
                                            <input type="radio" name="status_{{ $plan['id'] }}" value="not_finished" x-model="status" class="accent-danger">
                                            <span class="text-sm text-text">Tidak Selesai</span>
                                        </label>

                                        <div x-show="status && status !== 'finished'" x-transition>
                                            <label class="label mt-3">Alasan <span class="text-danger">*</span></label>
                                            <textarea class="input min-h-[80px]" rows="2" placeholder="Jelaskan alasan..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="label">Catatan Realisasi</label>
                                    <textarea class="input min-h-[60px]" rows="2" placeholder="Catatan tambahan..."></textarea>
                                </div>

                                {{-- TODO: wire:click="submitRealization({{ $plan['id'] }})" --}}
                                <button type="button" class="btn-primary w-full md:w-auto">
                                    Simpan Realisasi
                                </button>
                            </form>
                        </div>
                    </x-ui.card>
                @empty
                    <x-ui.empty-state
                        title="Tidak ada plan untuk direalisasikan"
                        description="Isi plan terlebih dahulu di tab Plan."
                        icon="clipboard"
                    />
                @endforelse
            </div>

            @if(count($existingPlans) > 0 && $isRealizationWindowOpen)
                <div class="fixed bottom-0 left-0 right-0 p-4 bg-surface border-t border-border md:static md:p-0 md:border-0 md:mt-6 md:bg-transparent z-30">
                    <button class="btn-primary w-full md:w-auto">
                        Submit Semua Realisasi
                    </button>
                </div>
                <div class="h-20 md:hidden"></div>
            @endif
        </div>
    </div>
</x-layouts.app>
