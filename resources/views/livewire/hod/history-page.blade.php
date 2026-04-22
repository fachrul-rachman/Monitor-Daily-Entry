<div>
    <x-ui.page-header title="Riwayat Entry" description="Lihat entry plan dan realisasi Anda sebelumnya">
        <x-slot:actions>
            <form class="flex flex-wrap items-end gap-2" wire:submit.prevent="applyFilters">
                <div class="w-40">
                    <label class="label">Dari</label>
                    <input type="date" class="input @error('from') input-error @enderror" wire:model.defer="from" />
                    @error('from') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="w-40">
                    <label class="label">Sampai</label>
                    <input type="date" class="input @error('to') input-error @enderror" wire:model.defer="to" />
                    @error('to') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-secondary px-4" wire:target="applyFilters" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="applyFilters">Terapkan</span>
                    <span wire:loading wire:target="applyFilters">Memuat…</span>
                </button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div x-data="{ drawerOpen: @entangle('drawerOpen') }">
        {{-- Timeline --}}
        <div class="space-y-6">
            @forelse($historyByDate as $date => $entries)
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
                            <x-ui.card
                                class="cursor-pointer hover:border-primary/30 transition-colors"
                                wire:click="openDetail({{ $entry['id'] }})"
                                wire:target="openDetail({{ $entry['id'] }})"
                                wire:loading.attr="disabled"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-text">{{ $entry['title'] }}</p>
                                    @if($entry['severity'])
                                        <x-ui.severity-badge :severity="$entry['severity']" />
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-sm mt-1.5">
                                    <span class="badge-primary">{{ $entry['big_rock'] }}</span>
                                    <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    <span class="badge-muted">{{ $entry['roadmap'] }}</span>
                                </div>
                                <div class="flex gap-3 mt-2 text-sm">
                                    <span class="text-muted">Plan:</span> <x-ui.status-badge :status="$entry['plan_status']" />
                                    <span class="text-muted ml-2">Real:</span> <x-ui.status-badge :status="$entry['realization_status']" />
                                </div>
                                <div class="text-sm text-muted mt-2" wire:loading wire:target="openDetail({{ $entry['id'] }})">
                                    Membuka detail…
                                </div>
                            </x-ui.card>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    icon="calendar"
                    title="Belum ada riwayat di periode ini"
                    description="Coba ubah tanggal, atau mulai isi Plan & Realisasi agar riwayat muncul."
                    class="py-12"
                />
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
                        <h3 class="font-semibold text-text truncate">Detail Entry</h3>
                        <p class="text-sm text-muted mt-0.5">{{ $selectedItem['date'] ?? '—' }}</p>
                    </div>
                    <button @click="$wire.closeDrawer()" class="text-muted hover:text-text">✕</button>
                </div>

                <div class="p-5 space-y-5">
                    <div>
                        <p class="text-base font-semibold text-text">{{ $selectedItem['title'] ?? '—' }}</p>
                        @if(!empty($selectedItem['severity']))
                            <div class="mt-2">
                                <x-ui.severity-badge :severity="$selectedItem['severity']" />
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-sm text-muted">Big Rock</p>
                            <p class="text-sm text-text">{{ $selectedItem['big_rock'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted">Roadmap</p>
                            <p class="text-sm text-text">{{ $selectedItem['roadmap'] ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-sm text-muted">Plan Status</p>
                            <x-ui.status-badge :status="$selectedItem['plan_status'] ?? 'missing'" />
                        </div>
                        <div>
                            <p class="text-sm text-muted">Realisasi Status</p>
                            <x-ui.status-badge :status="$selectedItem['realization_status'] ?? 'missing'" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide">Rencana</p>
                        @if(!empty($selectedItem['plan_text']))
                            <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                <p class="text-sm text-text whitespace-pre-line">{{ $selectedItem['plan_text'] }}</p>
                            </div>
                        @else
                            <p class="text-sm text-muted">Tidak ada deskripsi rencana.</p>
                        @endif

                        @if(!empty($selectedItem['plan_relation_reason']))
                            <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-1">Alasan Terkait Big Rock</p>
                                <p class="text-sm text-text whitespace-pre-line">{{ $selectedItem['plan_relation_reason'] }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide">Realisasi</p>
                        @if(!empty($selectedItem['realization_text']))
                            <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                <p class="text-sm text-text whitespace-pre-line">{{ $selectedItem['realization_text'] }}</p>
                            </div>
                        @else
                            <p class="text-sm text-muted">Belum ada isi realisasi.</p>
                        @endif

                        @if(!empty($selectedItem['realization_reason']))
                            <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-1">Alasan / Kendala</p>
                                <p class="text-sm text-text whitespace-pre-line">{{ $selectedItem['realization_reason'] }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide">Lampiran</p>
                        @if(empty($selectedAttachments))
                            <p class="text-sm text-muted">Tidak ada lampiran.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($selectedAttachments as $a)
                                    <div class="flex items-start justify-between gap-3 p-3 rounded-xl border border-border">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-text truncate">{{ $a['name'] }}</p>
                                            <p class="text-sm text-muted mt-0.5">
                                                @if($a['size_kb']) {{ $a['size_kb'] }} KB @else — @endif
                                            </p>
                                        </div>
                                        @if(!empty($a['url']))
                                            <a href="{{ $a['url'] }}" target="_blank" class="btn-secondary px-4">Buka</a>
                                        @else
                                            <span class="text-sm text-muted">—</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
