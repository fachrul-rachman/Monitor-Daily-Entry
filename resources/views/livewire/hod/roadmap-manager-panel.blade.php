{{--
    HoD Roadmap Manager Panel — Slide Over
    Component: App\Livewire\Hod\RoadmapManagerPanel
    Triggered from Big Rock page
--}}

@php
    $bigRockTitle = 'Optimasi Proses Operasional Q3';
    $roadmapItems = [
        ['id' => 1, 'title' => 'Implementasi SOP Baru', 'status' => 'in_progress', 'order' => 1],
        ['id' => 2, 'title' => 'Audit Proses Existing', 'status' => 'planned', 'order' => 2],
        ['id' => 3, 'title' => 'Sosialisasi ke Tim', 'status' => 'not_started', 'order' => 3],
        ['id' => 4, 'title' => 'Evaluasi & Perbaikan', 'status' => 'not_started', 'order' => 4],
    ];
@endphp

{{-- TODO: Ganti x-show dengan wire:model / Livewire property --}}
<div
    x-data="{ showPanel: true }"
    x-show="showPanel"
    class="fixed inset-0 z-40 flex justify-end"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/40" @click="showPanel = false"></div>

    <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
    >
        {{-- Header --}}
        <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface z-10">
            <div>
                <h3 class="font-semibold text-text">Roadmap Items</h3>
                <p class="text-sm text-muted mt-0.5">{{ $bigRockTitle }}</p>
            </div>
            <button @click="showPanel = false" class="text-muted hover:text-text text-lg">✕</button>
        </div>

        <div class="p-5">
            {{-- Roadmap item list --}}
            <div class="space-y-2 mb-6">
                @forelse($roadmapItems as $item)
                    <div class="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-app-bg transition-colors group">
                        {{-- Order number --}}
                        <span class="text-xs font-mono text-muted bg-app-bg w-6 h-6 rounded flex items-center justify-center shrink-0">{{ $item['order'] }}</span>

                        {{-- Title --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text truncate">{{ $item['title'] }}</p>
                        </div>

                        {{-- Status --}}
                        <x-ui.status-badge :status="$item['status']" />

                        {{-- Actions --}}
                        <div class="flex gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                            {{-- TODO: wire:click="editItem({{ $item['id'] }})" --}}
                            <button class="p-1.5 rounded hover:bg-app-bg text-muted hover:text-primary" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            {{-- TODO: wire:click="removeItem({{ $item['id'] }})" --}}
                            <button class="p-1.5 rounded hover:bg-app-bg text-muted hover:text-danger" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state
                        title="Belum ada roadmap item"
                        description="Tambah item pertama untuk memulai."
                        class="py-8"
                    />
                @endforelse
            </div>

            {{-- Inline add form --}}
            <div class="border-t border-border pt-5" x-data="{ adding: false }">
                <button x-show="!adding" @click="adding = true" class="btn-secondary w-full gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Tambah Roadmap Item
                </button>

                <div x-show="adding" x-transition style="display:none;">
                    <h4 class="text-sm font-semibold text-text mb-3">Tambah Roadmap Item</h4>
                    {{-- TODO: wire:submit.prevent="addItem" --}}
                    <form class="space-y-3">
                        <div>
                            <label class="label">Judul <span class="text-danger">*</span></label>
                            {{-- TODO: wire:model.defer="newItemTitle" --}}
                            <input type="text" class="input" placeholder="Judul roadmap item..." />
                        </div>
                        <div>
                            <label class="label">Status Awal</label>
                            {{-- TODO: wire:model="newItemStatus" --}}
                            <select class="input">
                                <option value="not_started">Not Started</option>
                                <option value="planned">Planned</option>
                                <option value="in_progress">In Progress</option>
                            </select>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="adding = false" class="btn-secondary flex-1">Batal</button>
                            <button type="submit" class="btn-primary flex-1">Tambah Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
