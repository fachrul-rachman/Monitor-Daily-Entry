<div>
    <x-ui.page-header title="History Off" description="Riwayat pengajuan off (HoD + Manager) beserta keputusan dan detailnya" />

    {{-- Summary chips --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <span class="badge-warning">Pending: {{ $summaryCount['pending'] ?? 0 }}</span>
        <span class="badge-success">Disetujui: {{ $summaryCount['approved'] ?? 0 }}</span>
        <span class="badge-danger">Ditolak: {{ $summaryCount['rejected'] ?? 0 }}</span>
        <span class="badge-muted">Cancelled: {{ $summaryCount['cancelled'] ?? 0 }}</span>
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
            <div class="w-52">
                <label class="label">Cari</label>
                <input type="text" placeholder="Nama / email..." class="input" wire:model.debounce.400ms="search" />
            </div>
            <div class="w-44">
                <label class="label">Status</label>
                <select class="input" wire:model="status">
                    <option value="">Semua</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                    <option value="cancelled">Cancelled</option>
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
            <div class="w-44">
                <label class="label">Tipe</label>
                <select class="input" wire:model="type">
                    <option value="">Semua</option>
                    @foreach($typeOptions as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-56">
                <label class="label">Diputuskan Oleh</label>
                <select class="input" wire:model="decidedBy">
                    <option value="">Semua</option>
                    @foreach($deciderOptions as $u)
                        <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
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
                        <input type="date" class="input @error('from') input-error @enderror" wire:model.defer="from" />
                        @error('from') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Sampai</label>
                        <input type="date" class="input @error('to') input-error @enderror" wire:model.defer="to" />
                        @error('to') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="label">Status</label>
                    <select class="input" wire:model="status">
                        <option value="">Semua</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                        <option value="cancelled">Cancelled</option>
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

                <div>
                    <label class="label">Diputuskan Oleh</label>
                    <select class="input" wire:model="decidedBy">
                        <option value="">Semua</option>
                        @foreach($deciderOptions as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" class="btn-primary flex-1" @click="filterOpen = false" wire:click="applyFilters">Terapkan</button>
                    <button type="button" class="btn-secondary px-4" wire:click="resetFilters">Reset</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-muted">
                    <tr class="border-b border-border">
                        <th class="text-left py-3 pr-4">Nama</th>
                        <th class="text-left py-3 pr-4">Divisi</th>
                        <th class="text-left py-3 pr-4">Tipe</th>
                        <th class="text-left py-3 pr-4">Tanggal</th>
                        <th class="text-left py-3 pr-4">Status</th>
                        <th class="text-left py-3 pr-4">Diputuskan</th>
                        <th class="text-right py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests as $r)
                        <tr class="border-b border-border/60">
                            <td class="py-3 pr-4">
                                <p class="font-semibold text-text">{{ $r['user'] }}</p>
                                <p class="text-xs text-muted">Submitted: {{ $r['submitted'] }}</p>
                            </td>
                            <td class="py-3 pr-4 text-muted">{{ $r['division'] }}</td>
                            <td class="py-3 pr-4 text-muted">{{ $r['type'] }}</td>
                            <td class="py-3 pr-4 text-muted">{{ $r['date'] }}</td>
                            <td class="py-3 pr-4">
                                <x-ui.status-badge :status="$r['status']" />
                            </td>
                            <td class="py-3 pr-4 text-muted">
                                <p>{{ $r['decided_by'] }}</p>
                                <p class="text-xs text-muted">{{ $r['decided_at'] }}</p>
                            </td>
                            <td class="py-3 text-right">
                                <button type="button" class="btn-secondary px-4" wire:click="openDetail({{ $r['id'] }})">Buka</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-muted">Tidak ada data pada rentang filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rows->links() }}
        </div>
    </x-ui.card>

    {{-- Drawer detail --}}
    <div x-data="{ open: @entangle('drawerOpen') }" x-show="open" class="fixed inset-0 z-50" style="display:none;">
        <div class="absolute inset-0 bg-black/40" @click="open = false; $wire.closeDrawer()"></div>
        <div class="absolute right-0 top-0 bottom-0 w-full md:w-[520px] bg-surface border-l border-border p-6 overflow-y-auto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <div class="flex items-start justify-between gap-4 mb-6">
                <div class="min-w-0">
                    <p class="text-lg font-semibold text-text truncate">{{ $selected['user'] ?? '—' }}</p>
                    <p class="text-sm text-muted mt-0.5">
                        {{ $selected['division'] ?? '—' }} · {{ $selected['role'] ?? '—' }} · {{ $selected['type'] ?? '—' }}
                    </p>
                    <p class="text-sm text-muted mt-0.5 truncate">{{ $selected['email'] ?? '—' }}</p>
                </div>
                <div class="shrink-0">
                    @if(!empty($selected['status']))
                        <x-ui.status-badge :status="$selected['status']" />
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-5">
                <div>
                    <p class="text-sm text-muted">Tanggal</p>
                    <p class="text-sm text-text">{{ $selected['date'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted">Diajukan</p>
                    <p class="text-sm text-text">{{ $selected['submitted'] ?? '—' }}</p>
                </div>
            </div>

            <div class="mb-5">
                <p class="text-sm text-muted mb-1">Alasan</p>
                <p class="text-sm text-text whitespace-pre-line">{{ $selected['reason'] ?? '—' }}</p>
            </div>

            @if(!empty($selected['decision_note']))
                <div class="mb-5">
                    <p class="text-sm text-muted mb-1">Catatan Keputusan</p>
                    <p class="text-sm text-text whitespace-pre-line">{{ $selected['decision_note'] }}</p>
                </div>
            @endif

            @php
                $att = $selected['attachment'] ?? null;
            @endphp
            @if(is_array($att) && ! empty($att['path']))
                <div class="mb-5">
                    <p class="text-sm text-muted mb-1">Lampiran</p>
                    <a
                        class="text-sm text-primary underline"
                        href="{{ \Illuminate\Support\Facades\Storage::url($att['path']) }}"
                        target="_blank"
                        rel="noopener"
                    >
                        {{ $att['name'] ?: basename($att['path']) }}
                    </a>
                    <p class="text-xs text-muted mt-0.5">Klik untuk membuka di tab baru.</p>
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
                                <p class="text-sm font-medium text-text">
                                    {{ ($selected['status'] ?? null) === 'cancelled' ? 'Cancelled' : 'Ditolak' }}
                                </p>
                                <p class="text-sm text-muted mt-0.5">{{ $selected['rejected_by'] }} · {{ $selected['rejected_at'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="pt-6">
                <button type="button" class="btn-secondary w-full" @click="open = false; $wire.closeDrawer()">Tutup</button>
            </div>
        </div>
    </div>
</div>
