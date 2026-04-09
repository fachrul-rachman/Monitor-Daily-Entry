{{--
    HoD Team Big Rock Page
    Route: /hod/team-big-rock
    Component: App\Livewire\Hod\TeamBigRockPage
--}}

<div>
    <x-ui.page-header
        title="Big Rock Tim"
        description="Lihat Big Rock Manager per divisi (read-only)"
    >
        <x-slot:actions>
            <div class="w-64">
                <label class="label">Divisi</label>
                <select class="input" wire:model.live="division">
                    @forelse($divisionOptions as $opt)
                        <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                    @empty
                        <option value="">Belum ada divisi</option>
                    @endforelse
                </select>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="rounded-xl border border-border bg-app-bg px-4 py-3 mb-4">
        <p class="text-sm text-text font-medium">Catatan</p>
        <p class="text-xs text-muted mt-1">HoD bisa melihat Big Rock Manager untuk monitoring, tapi tidak bisa mengedit atau menggunakannya di plan/realisasi.</p>
    </div>

    <div x-data="{ drawerOpen: @entangle('drawerOpen') }">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($rows as $row)
                <x-ui.card class="flex flex-col">
                    <div class="flex items-start justify-between mb-2">
                        <x-ui.status-badge :status="$row['status']" />
                        <span class="badge-muted">Read-only</span>
                    </div>

                    <h4 class="text-sm font-semibold text-text mb-1">{{ $row['title'] }}</h4>
                    <p class="text-xs text-muted line-clamp-2 mb-3">{{ $row['description'] }}</p>

                    <div class="text-xs text-muted mb-3">
                        {{ $row['start'] }} &mdash; {{ $row['end'] }}
                    </div>

                    <div class="mb-3">
                        <div class="flex items-center justify-between text-xs text-muted mb-1">
                            <span>Progress</span>
                            <span>{{ $row['progress'] }}%</span>
                        </div>
                        <div class="w-full h-2 bg-surface rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all {{ $row['progress'] >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $row['progress'] }}%"></div>
                        </div>
                    </div>

                    <div class="mt-auto pt-3 border-t border-border">
                        <p class="text-xs text-muted">{{ $row['owner'] }}</p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs text-muted">{{ $row['roadmap_count'] }} roadmap items</span>
                            <button
                                type="button"
                                class="btn-secondary px-4"
                                wire:click="openDetail({{ $row['id'] }})"
                                wire:target="openDetail({{ $row['id'] }})"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="openDetail({{ $row['id'] }})">Lihat Detail</span>
                                <span wire:loading wire:target="openDetail({{ $row['id'] }})">Membuka...</span>
                            </button>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <div class="lg:col-span-3">
                    <x-ui.empty-state
                        icon="document"
                        title="Belum ada Big Rock Manager"
                        description="Pilih divisi lain, atau pastikan Manager di divisi tersebut sudah punya Big Rock."
                        class="py-12"
                    />
                </div>
            @endforelse
        </div>

        {{-- Detail Drawer --}}
        <div x-show="drawerOpen" class="fixed inset-0 z-40 flex justify-end" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="$wire.closeDrawer()"></div>
            <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0">
                <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface z-10">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-text truncate">{{ $selected['title'] ?? 'Detail Big Rock' }}</h3>
                        <p class="text-xs text-muted mt-0.5">{{ $selected['owner'] ?? '-' }}</p>
                    </div>
                    <button type="button" class="btn-secondary px-4" wire:click="closeDrawer">Tutup</button>
                </div>

                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-muted">Status</p>
                            <x-ui.status-badge :status="$selected['status'] ?? 'active'" />
                        </div>
                        <div>
                            <p class="text-xs text-muted">Periode</p>
                            <p class="text-sm text-text">{{ $selected['start'] ?? '-' }} &mdash; {{ $selected['end'] ?? '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide">Deskripsi</p>
                        <div class="rounded-xl border border-border bg-app-bg px-4 py-3 mt-2">
                            <p class="text-sm text-text whitespace-pre-line">{{ $selected['description'] ?? '' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-2">Roadmap Items</p>
                        @forelse($selectedRoadmaps as $rm)
                            <div class="p-4 rounded-xl border border-border bg-surface flex items-start justify-between gap-3 mb-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-text">{{ $rm['title'] }}</p>
                                    <p class="text-xs text-muted mt-1">Urutan: {{ $rm['sort_order'] }}</p>
                                </div>
                                <x-ui.status-badge :status="$rm['status']" />
                            </div>
                        @empty
                            <x-ui.empty-state title="Belum ada roadmap item" icon="document" class="py-10" />
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

