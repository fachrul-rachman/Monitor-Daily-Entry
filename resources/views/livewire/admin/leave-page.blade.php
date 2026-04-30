<div>
    <x-ui.page-header title="Pengajuan Off" description="Kelola permintaan off karyawan" />

    {{-- Summary chips --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <span class="badge-warning">Pending: {{ $summaryCount['pending'] ?? 0 }}</span>
        <span class="badge-success">Disetujui: {{ $summaryCount['approved'] ?? 0 }}</span>
        <span class="badge-danger">Ditolak: {{ $summaryCount['rejected'] ?? 0 }}</span>
    </div>

    {{-- Filter bar --}}
    <div class="mb-6" x-data="{ filterOpen: false }">
        <form class="hidden md:flex gap-3 flex-wrap items-end" wire:submit.prevent="applyFilters">
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
            <div class="w-40">
                <label class="label">Status</label>
                <select class="input" wire:model="status">
                    <option value="">Semua</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
            <div class="w-48">
                <label class="label">Divisi</label>
                <select class="input" wire:model="division">
                    <option value="">Semua</option>
                    @foreach($divisionOptions as $d)
                        <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <label class="label">Tipe</label>
                <select class="input" wire:model="type">
                    <option value="">Semua</option>
                    @foreach($typeOptions as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 pb-1">
                <button type="submit" class="btn-secondary px-4" wire:target="applyFilters" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="applyFilters">Terapkan</span>
                    <span wire:loading wire:target="applyFilters">Memuat…</span>
                </button>
                <button type="button" class="text-sm text-muted hover:text-text" wire:click="resetFilters">Reset</button>
            </div>
        </form>

        <div class="flex gap-2 items-center md:hidden">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" placeholder="Cari nama/email…" class="input pl-9" wire:model.debounce.400ms="search">
            </div>
            <button type="button" @click="filterOpen = true" class="btn-secondary gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
        </div>

        {{-- Mobile bottom sheet --}}
        <div x-show="filterOpen" class="fixed inset-0 z-40 md:hidden" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="filterOpen = false"></div>
            <div class="absolute bottom-0 left-0 right-0 bg-surface rounded-t-2xl p-5 space-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-text">Filter</p>
                    <button type="button" @click="filterOpen = false" class="text-muted text-lg">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Dari</label>
                        <input type="date" class="input" wire:model.defer="from" />
                    </div>
                    <div>
                        <label class="label">Sampai</label>
                        <input type="date" class="input" wire:model.defer="to" />
                    </div>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select class="input" wire:model="status">
                        <option value="">Semua</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </div>
                <div>
                    <label class="label">Divisi</label>
                    <select class="input" wire:model="division">
                        <option value="">Semua</option>
                        @foreach($divisionOptions as $d)
                            <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Tipe</label>
                    <select class="input" wire:model="type">
                        <option value="">Semua</option>
                        @foreach($typeOptions as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" class="btn-secondary flex-1" wire:click="resetFilters" @click="filterOpen = false">Reset</button>
                    <button type="button" class="btn-primary flex-1" wire:click="applyFilters" wire:target="applyFilters" wire:loading.attr="disabled" @click="filterOpen = false">
                        <span wire:loading.remove wire:target="applyFilters">Terapkan</span>
                        <span wire:loading wire:target="applyFilters">Memuat…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{ drawerOpen: @entangle('drawerOpen') }">
        {{-- Leave list --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($leaveRequests as $leave)
                <x-ui.card class="cursor-pointer hover:border-primary/30 transition-colors"
                    wire:click="openDetail({{ $leave['id'] }})"
                    wire:target="openDetail({{ $leave['id'] }})"
                    wire:loading.attr="disabled"
                >
                    <div class="flex items-start justify-between">
                        <div class="min-w-0">
                            <p class="font-semibold text-text truncate">{{ $leave['user'] }}</p>
                            <p class="text-sm text-muted mt-0.5">{{ $leave['division'] }} · {{ $leave['type'] }}</p>
                        </div>
                        <x-ui.status-badge :status="$leave['status']" />
                    </div>
                    <p class="text-sm text-muted mt-2">{{ $leave['date'] }}</p>
                    @if(!empty($leave['reason']))
                        <p class="text-sm text-muted mt-1 line-clamp-2">{{ $leave['reason'] }}</p>
                    @endif
                    <div class="text-sm text-muted mt-2" wire:loading wire:target="openDetail({{ $leave['id'] }})">
                        Membuka detail…
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty-state title="Tidak ada permintaan cuti pada periode ini" icon="calendar" />
            @endforelse
        </div>

        <div class="mt-6">
            {{ $rows->links() }}
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
                        <h3 class="font-semibold text-text truncate">Detail Permintaan</h3>
                        <p class="text-sm text-muted mt-0.5">{{ $selected['submitted'] ?? '—' }}</p>
                    </div>
                    <button type="button" @click="$wire.closeDrawer()" class="text-muted hover:text-text">✕</button>
                </div>

                <div class="p-5 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-base font-semibold text-text truncate">{{ $selected['user'] ?? '—' }}</p>
                            <p class="text-sm text-muted mt-0.5">{{ $selected['division'] ?? '—' }} · {{ $selected['type'] ?? '—' }}</p>
                        </div>
                        @if(!empty($selected['status']))
                            <x-ui.status-badge :status="$selected['status']" />
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-sm text-muted">Tanggal</p>
                            <p class="text-sm text-text">{{ $selected['date'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted">Status</p>
                            <p class="text-sm text-text">{{ $selected['status'] ?? '—' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-muted mb-1">Alasan</p>
                        <p class="text-sm text-text whitespace-pre-line">{{ $selected['reason'] ?? '—' }}</p>
                    </div>

                    {{-- Actions for pending --}}
                    @if(($selected['status'] ?? null) === 'pending')
                        <div class="flex gap-3 pt-4 border-t border-border">
                            <button type="button" class="btn-primary flex-1"
                                wire:click="approveSelected"
                                wire:target="approveSelected"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="approveSelected">Setujui</span>
                                <span wire:loading wire:target="approveSelected">Menyimpan…</span>
                            </button>
                            <button type="button" class="btn-danger flex-1"
                                wire:click="rejectSelected"
                                wire:confirm="Yakin ingin menolak permintaan cuti ini? Aksi ini tidak bisa dibatalkan."
                                wire:target="rejectSelected"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="rejectSelected">Tolak</span>
                                <span wire:loading wire:target="rejectSelected">Menyimpan…</span>
                            </button>
                        </div>
                    @endif

                    {{-- Audit trail --}}
                    <div class="pt-4 border-t border-border">
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-3">Riwayat</p>
                        <div class="space-y-3">
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></div>
                                    <div class="w-px flex-1 bg-border mt-1"></div>
                                </div>
                                <div class="pb-3">
                                    <p class="text-sm font-medium text-text">Permintaan diajukan</p>
                                    <p class="text-sm text-muted mt-0.5">{{ $selected['user'] ?? '' }} · {{ $selected['submitted'] ?? '' }}</p>
                                </div>
                            </div>

                            @if(!empty($selected['approved_at']) && !empty($selected['approved_by']))
                                <div class="flex gap-3">
                                    <div class="flex flex-col items-center">
                                        <div class="w-2 h-2 rounded-full bg-success mt-1.5 shrink-0"></div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-text">Disetujui</p>
                                        <p class="text-sm text-muted mt-0.5">{{ $selected['approved_by'] }} · {{ $selected['approved_at'] }}</p>
                                    </div>
                                </div>
                            @endif

                            @if(!empty($selected['rejected_at']) && !empty($selected['rejected_by']))
                                <div class="flex gap-3">
                                    <div class="flex flex-col items-center">
                                        <div class="w-2 h-2 rounded-full bg-danger mt-1.5 shrink-0"></div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-text">Ditolak</p>
                                        <p class="text-sm text-muted mt-0.5">{{ $selected['rejected_by'] }} · {{ $selected['rejected_at'] }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
