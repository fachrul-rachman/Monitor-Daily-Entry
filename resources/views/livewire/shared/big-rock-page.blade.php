{{--
    Shared Big Rock Page (Manager & HoD - personal only)
    Component: App\Livewire\Shared\BigRockPage
--}}

<div>
    <x-ui.page-header title="Big Rock" description="Kelola big rock dan roadmap item (punya Anda)">
        @if($canManageBigRock)
            <x-slot:actions>
                <button class="btn-primary gap-2" wire:click="openCreateBigRock" wire:target="openCreateBigRock" wire:loading.attr="disabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span wire:loading.remove wire:target="openCreateBigRock">Tambah Big Rock</span>
                    <span wire:loading wire:target="openCreateBigRock">Membuka...</span>
                </button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div x-data="{
        drawerOpen: @entangle('roadmapDrawerOpen'),
        modalOpen: @entangle('bigRockModalOpen'),
        roadmapModalOpen: @entangle('roadmapModalOpen'),
    }">
        {{-- Big Rock Card List --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($bigRocks as $br)
                <x-ui.card class="flex flex-col">
                    <div class="flex items-start justify-between mb-2">
                        <x-ui.status-badge :status="$br['status']" />
                        @if($canManageBigRock)
                            <div class="flex items-center gap-1" x-data="{ menuOpen: false }">
                                <div class="relative">
                                    <button type="button" @click.stop="menuOpen = !menuOpen" class="p-1 rounded hover:bg-app-bg text-muted hover:text-text">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                                    </button>
                                    <div x-show="menuOpen" @click.away="menuOpen = false" class="absolute right-0 mt-1 w-40 bg-surface border border-border rounded-lg shadow-lg z-10 py-1" style="display:none;">
                                        <button type="button" class="w-full text-left px-3 py-2 text-sm text-text hover:bg-app-bg"
                                            @click="menuOpen = false"
                                            wire:click="openEditBigRock({{ $br['id'] }})"
                                            wire:target="openEditBigRock({{ $br['id'] }})"
                                            wire:loading.attr="disabled"
                                        >Edit</button>
                                        <button type="button" class="w-full text-left px-3 py-2 text-sm text-danger hover:bg-app-bg"
                                            @click="menuOpen = false"
                                            wire:click="archiveBigRock({{ $br['id'] }})"
                                            wire:target="archiveBigRock({{ $br['id'] }})"
                                            wire:loading.attr="disabled"
                                        >Arsipkan</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <h4 class="text-sm font-semibold text-text mb-1">{{ $br['title'] }}</h4>
                    <p class="text-sm text-muted line-clamp-2 mb-3">{{ $br['description'] }}</p>

                    <div class="text-sm text-muted mb-3">
                        {{ $br['start'] }} &mdash; {{ $br['end'] }}
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
                            type="button"
                            class="text-sm text-primary font-medium hover:underline"
                            wire:click="openRoadmapDrawer({{ $br['id'] }})"
                            wire:target="openRoadmapDrawer({{ $br['id'] }})"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="openRoadmapDrawer({{ $br['id'] }})">Kelola Roadmap &rarr;</span>
                            <span wire:loading wire:target="openRoadmapDrawer({{ $br['id'] }})">Membuka...</span>
                        </button>
                    </div>
                </x-ui.card>
            @empty
                <div class="lg:col-span-3">
                    <x-ui.empty-state
                        icon="document"
                        title="Belum ada Big Rock"
                        description="Tambahkan Big Rock agar pekerjaan harian punya arah yang jelas."
                        class="py-12"
                    />
                </div>
            @endforelse
        </div>

        {{-- Roadmap Drawer --}}
        <div x-show="drawerOpen" class="fixed inset-0 z-40 flex justify-end" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="$wire.closeRoadmapDrawer()"></div>
            <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0">
                <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface z-10">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-text truncate">{{ $selectedBigRockTitle ?: 'Roadmap' }}</h3>
                        <p class="text-sm text-muted mt-0.5">Roadmap item untuk Big Rock ini</p>
                    </div>
                    <button type="button" class="btn-secondary px-4" wire:click="closeRoadmapDrawer">Tutup</button>
                </div>

                <div class="p-5 space-y-3">
                    @if($canManageBigRock)
                        <button class="btn-primary w-full justify-center gap-2" wire:click="openCreateRoadmap" wire:target="openCreateRoadmap" wire:loading.attr="disabled">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span wire:loading.remove wire:target="openCreateRoadmap">Tambah Roadmap Item</span>
                            <span wire:loading wire:target="openCreateRoadmap">Membuka...</span>
                        </button>
                    @endif

                    @forelse($roadmapItems as $rm)
                        <div class="p-4 rounded-xl border border-border bg-surface flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-text">{{ $rm['title'] }}</p>
                                <p class="text-sm text-muted mt-1">Urutan: {{ $rm['sort_order'] }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <x-ui.status-badge :status="$rm['status']" />
                                @if($canManageBigRock)
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="btn-secondary px-3"
                                            wire:click="openEditRoadmap({{ $rm['id'] }})"
                                            wire:target="openEditRoadmap({{ $rm['id'] }})"
                                            wire:loading.attr="disabled"
                                        >Edit</button>
                                        <button type="button" class="btn-danger px-3"
                                            wire:click="archiveRoadmap({{ $rm['id'] }})"
                                            wire:target="archiveRoadmap({{ $rm['id'] }})"
                                            wire:loading.attr="disabled"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V4h6v3m2 0v13a2 2 0 01-2 2H9a2 2 0 01-2-2V7h10z"/></svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                            <p class="text-sm font-semibold text-text">Belum ada roadmap item</p>
                            <p class="text-sm text-muted mt-1">Tambahkan roadmap item untuk memecah Big Rock menjadi langkah-langkah.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Big Rock Modal --}}
        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none;"
        >
            <div class="absolute inset-0 bg-black/40" @click="$wire.bigRockModalOpen = false"></div>
            <div class="relative bg-surface rounded-2xl p-6 w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">
                        {{ $editingBigRockId ? 'Edit Big Rock' : 'Tambah Big Rock' }}
                    </h3>
                    <button type="button" @click="$wire.bigRockModalOpen = false" class="text-muted hover:text-text text-lg">&times;</button>
                </div>

                <form class="space-y-4" wire:submit.prevent="saveBigRock">
                    <div>
                        <label class="label">Judul</label>
                        <input type="text" class="input @error('bigRockTitle') input-error @enderror" wire:model.live="bigRockTitle" />
                        @error('bigRockTitle') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Deskripsi</label>
                        <textarea class="input min-h-[110px] @error('bigRockDescription') input-error @enderror" wire:model.live="bigRockDescription"></textarea>
                        @error('bigRockDescription') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Mulai</label>
                            <input type="date" class="input @error('bigRockStartDate') input-error @enderror" wire:model.live="bigRockStartDate" />
                            @error('bigRockStartDate') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Selesai</label>
                            <input type="date" class="input @error('bigRockEndDate') input-error @enderror" wire:model.live="bigRockEndDate" />
                            @error('bigRockEndDate') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">Status</label>
                        <select class="input @error('bigRockStatus') input-error @enderror" wire:model.live="bigRockStatus">
                            <option value="active">Active</option>
                            <option value="on_track">On Track</option>
                            <option value="at_risk">At Risk</option>
                            <option value="completed">Completed</option>
                            <option value="archived">Archived</option>
                        </select>
                        @error('bigRockStatus') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="btn-secondary px-5" @click="$wire.bigRockModalOpen = false">Batal</button>
                        <button type="submit" class="btn-primary px-5" wire:target="saveBigRock" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveBigRock">Simpan</span>
                            <span wire:loading wire:target="saveBigRock">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Roadmap Modal --}}
        <div
            x-show="roadmapModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none;"
        >
            <div class="absolute inset-0 bg-black/40" @click="$wire.roadmapModalOpen = false"></div>
            <div class="relative bg-surface rounded-2xl p-6 w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">
                        {{ $editingRoadmapId ? 'Edit Roadmap Item' : 'Tambah Roadmap Item' }}
                    </h3>
                    <button type="button" @click="$wire.roadmapModalOpen = false" class="text-muted hover:text-text text-lg">&times;</button>
                </div>

                <form class="space-y-4" wire:submit.prevent="saveRoadmap">
                    <div>
                        <label class="label">Judul</label>
                        <input type="text" class="input @error('roadmapTitle') input-error @enderror" wire:model.live="roadmapTitle" />
                        @error('roadmapTitle') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Status</label>
                            <select class="input @error('roadmapStatus') input-error @enderror" wire:model.live="roadmapStatus">
                                <option value="planned">Planned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="blocked">Blocked</option>
                                <option value="finished">Finished</option>
                                <option value="archived">Archived</option>
                            </select>
                            @error('roadmapStatus') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Urutan</label>
                            <input type="number" class="input @error('roadmapSortOrder') input-error @enderror" wire:model.live="roadmapSortOrder" />
                            @error('roadmapSortOrder') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="btn-secondary px-5" @click="$wire.roadmapModalOpen = false">Batal</button>
                        <button type="submit" class="btn-primary px-5" wire:target="saveRoadmap" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveRoadmap">Simpan</span>
                            <span wire:loading wire:target="saveRoadmap">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
