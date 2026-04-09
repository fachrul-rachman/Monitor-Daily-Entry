<div>
    <x-ui.page-header
        title="Riwayat Notifikasi"
        description="Melihat notifikasi yang dikirim sistem (Discord, dll) dan jika ada kendala."
    />

    {{-- Filter bar --}}
    <div class="mb-6" x-data="{ filterOpen: false, detailOpen: @entangle('drawerOpen').live }">
        {{-- Desktop --}}
        <form class="hidden md:flex gap-3 flex-wrap items-end" wire:submit.prevent="applyFilters">
            <div class="w-56">
                <label class="label">Cari</label>
                <input
                    type="text"
                    class="input"
                    placeholder="Cari ringkasan atau error..."
                    wire:model.live.debounce.400ms="search"
                />
            </div>

            <div class="w-40">
                <label class="label">Dari</label>
                <input type="date" class="input @error('from') input-error @enderror" wire:model.defer="from" />
                @error('from') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="w-40">
                <label class="label">Sampai</label>
                <input type="date" class="input @error('to') input-error @enderror" wire:model.defer="to" />
                @error('to') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="w-40">
                <label class="label">Status</label>
                <select class="input" wire:model.live="status">
                    <option value="">Semua</option>
                    <option value="sent">Terkirim</option>
                    <option value="failed">Gagal</option>
                    <option value="skipped">Tidak dikirim</option>
                </select>
            </div>

            <div class="w-44">
                <label class="label">Channel</label>
                <select class="input" wire:model.live="channel">
                    <option value="">Semua</option>
                    @foreach($channelOptions as $c)
                        <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-52">
                <label class="label">Tipe</label>
                <select class="input" wire:model.live="type">
                    <option value="">Semua</option>
                    @foreach($typeOptions as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2 pb-1">
                <button type="submit" class="btn-secondary px-4" wire:target="applyFilters" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="applyFilters">Terapkan</span>
                    <span wire:loading wire:target="applyFilters">Memuat...</span>
                </button>
                <button type="button" class="text-sm text-muted hover:text-text" wire:click="resetFilters">Reset</button>
            </div>
        </form>

        {{-- Mobile --}}
        <div class="flex gap-2 items-center md:hidden">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" placeholder="Cari ringkasan/error..." class="input pl-9" wire:model.live.debounce.400ms="search" />
            </div>
            <button type="button" @click="filterOpen = true" class="btn-secondary gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
        </div>

        {{-- Mobile bottom sheet --}}
        <div x-show="filterOpen" class="fixed inset-0 z-40 md:hidden" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="filterOpen = false"></div>
            <div
                class="absolute bottom-0 left-0 right-0 bg-surface rounded-t-2xl p-5 space-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
            >
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-text">Filter</p>
                    <button type="button" @click="filterOpen = false" class="text-muted text-lg">×</button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Dari</label>
                        <input type="date" class="input @error('from') input-error @enderror" wire:model.defer="from" />
                        @error('from') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Sampai</label>
                        <input type="date" class="input @error('to') input-error @enderror" wire:model.defer="to" />
                        @error('to') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Status</label>
                        <select class="input" wire:model.live="status">
                            <option value="">Semua</option>
                            <option value="sent">Terkirim</option>
                            <option value="failed">Gagal</option>
                            <option value="skipped">Tidak dikirim</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Channel</label>
                        <select class="input" wire:model.live="channel">
                            <option value="">Semua</option>
                            @foreach($channelOptions as $c)
                                <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">Tipe</label>
                    <select class="input" wire:model.live="type">
                        <option value="">Semua</option>
                        @foreach($typeOptions as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button
                        type="button"
                        class="btn-primary flex-1"
                        @click="filterOpen = false"
                        wire:click="applyFilters"
                        wire:target="applyFilters"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="applyFilters">Terapkan</span>
                        <span wire:loading wire:target="applyFilters">Memuat...</span>
                    </button>
                    <button type="button" class="btn-secondary flex-1" wire:click="resetFilters">Reset</button>
                </div>
            </div>
        </div>

        {{-- Data table --}}
        <div class="hidden md:block mt-6">
            <x-ui.card class="p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-app-bg text-muted text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Waktu</th>
                                <th class="px-4 py-3 text-left">Channel</th>
                                <th class="px-4 py-3 text-left">Tipe</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Ringkasan</th>
                                <th class="px-4 py-3 text-right">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($items as $row)
                                <tr class="hover:bg-app-bg transition-colors">
                                    <td class="px-4 py-3.5 text-muted whitespace-nowrap">{{ $row['time'] }}</td>
                                    <td class="px-4 py-3.5 text-text whitespace-nowrap">{{ ucfirst($row['channel']) }}</td>
                                    <td class="px-4 py-3.5 text-text whitespace-nowrap">{{ $row['type'] }}</td>
                                    <td class="px-4 py-3.5"><x-ui.status-badge :status="$row['status']" /></td>
                                    <td class="px-4 py-3.5 text-text max-w-[480px] truncate">{{ $row['summary'] }}</td>
                                    <td class="px-4 py-3.5 text-right">
                                        <button
                                            type="button"
                                            class="btn-secondary px-3 py-2"
                                            wire:click="openDetail({{ $row['id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="openDetail"
                                        >
                                            Lihat
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10">
                                        <x-ui.empty-state icon="bell" title="Belum ada riwayat notifikasi" description="Coba test kirim Discord dari Settings, atau tunggu jadwal pengiriman harian." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        {{-- Mobile cards --}}
        <div class="block md:hidden mt-6 space-y-3">
            @forelse($items as $row)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-text truncate">{{ $row['summary'] }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ $row['time'] }} · {{ ucfirst($row['channel']) }} · {{ $row['type'] }}</p>
                        </div>
                        <x-ui.status-badge :status="$row['status']" />
                    </div>

                    <div class="mt-3">
                        <button
                            type="button"
                            class="btn-secondary w-full"
                            wire:click="openDetail({{ $row['id'] }})"
                            wire:loading.attr="disabled"
                            wire:target="openDetail"
                        >
                            Lihat Detail
                        </button>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty-state icon="bell" title="Belum ada riwayat notifikasi" description="Coba test kirim Discord dari Settings, atau tunggu jadwal pengiriman harian." />
            @endforelse
        </div>

        <div class="mt-6">
            <x-ui.pagination :paginator="$rows" />
        </div>

        {{-- Detail drawer --}}
        <div x-show="detailOpen" class="fixed inset-0 z-50" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="detailOpen = false; $wire.closeDrawer()"></div>

            <div
                class="absolute right-0 top-0 bottom-0 w-full md:w-[520px] bg-surface shadow-xl border-l border-border p-5 overflow-y-auto"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-text">Detail Notifikasi</p>
                        <p class="text-xs text-muted mt-0.5">Ringkasan dan payload yang dikirim.</p>
                    </div>
                    <button type="button" class="btn-secondary px-3 py-2" @click="detailOpen = false; $wire.closeDrawer()">
                        Tutup
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="bg-app-bg border border-border rounded-xl p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-text">{{ $selected['summary'] ?? '-' }}</p>
                                <p class="text-xs text-muted mt-1">
                                    {{ ucfirst($selected['channel'] ?? '-') }} · {{ $selected['type'] ?? '-' }} ·
                                    {{ $selected['context_date'] ?? '-' }}
                                </p>
                            </div>
                            <x-ui.status-badge :status="($selected['status'] ?? 'sent')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-surface border border-border rounded-xl p-4">
                            <p class="text-xs text-muted">Dibuat</p>
                            <p class="text-sm font-medium text-text mt-1">{{ $selected['created_at'] ?? '-' }}</p>
                        </div>
                        <div class="bg-surface border border-border rounded-xl p-4">
                            <p class="text-xs text-muted">Dikirim / Gagal</p>
                            <p class="text-sm font-medium text-text mt-1">
                                {{ $selected['sent_at'] ?? '-' }}
                                @if(!empty($selected['failed_at']))
                                    <span class="text-muted">/</span> {{ $selected['failed_at'] }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="bg-surface border border-border rounded-xl p-4">
                        <p class="text-xs font-semibold text-muted uppercase">Payload</p>
                        <pre class="mt-2 text-xs text-text bg-app-bg rounded-lg p-3 overflow-x-auto whitespace-pre-wrap">{{ json_encode($selected['payload'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    @if(!empty($selected['error_message']))
                        <div class="bg-danger-bg border border-danger/30 rounded-xl p-4">
                            <p class="text-xs font-semibold text-danger uppercase">Error</p>
                            <p class="text-sm text-danger mt-2 whitespace-pre-wrap">{{ $selected['error_message'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

