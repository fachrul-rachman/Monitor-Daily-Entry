{{--
    HoD Division Entries Page
    Route: /hod/division-entries
    Component: App\Livewire\Hod\DivisionEntriesPage
--}}

<div>
    <x-ui.page-header title="Entry Divisi" description="Lihat entry plan dan realisasi seluruh Manager di divisi Anda">
        <x-slot:actions>
            <form class="flex flex-wrap items-end gap-2" wire:submit.prevent="applyFilters">
                @if(!empty($divisionOptions))
                    <div class="w-52">
                        <label class="label">Divisi</label>
                        <select class="input" wire:model.defer="divisionId" @disabled(count($divisionOptions) === 1)>
                            @foreach($divisionOptions as $d)
                                <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                            @endforeach
                        </select>
                        @if(count($divisionOptions) === 1)
                            <p class="text-sm text-muted mt-1">Anda hanya memegang 1 divisi.</p>
                        @endif
                    </div>
                @endif
                <div class="w-40">
                    <label class="label">Tanggal</label>
                    <input type="date" class="input @error('date') input-error @enderror" wire:model.defer="date" />
                    @error('date') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="w-52">
                    <label class="label">User</label>
                    <select class="input" wire:model.defer="user">
                        <option value="">Semua Manager</option>
                        @foreach($managerOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-secondary px-4" wire:target="applyFilters" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="applyFilters">Terapkan</span>
                    <span wire:loading wire:target="applyFilters">Memuat...</span>
                </button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-wrap gap-3 mb-4 items-center">
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer min-h-[44px] transition-colors {{ $findingsOnly ? 'border-danger bg-danger-bg text-danger' : 'border-border bg-surface text-text' }}">
            <input type="checkbox" wire:model.live="findingsOnly" class="w-4 h-4 rounded accent-danger border-border">
            <span class="text-sm font-medium">Hanya Temuan</span>
        </label>

        @if($isWeekend)
            <span class="text-sm text-muted">Catatan: Hari ini weekend, data bisa kosong.</span>
        @endif
    </div>

    <div x-data="{ drawerOpen: @entangle('drawerOpen') }">
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-app-bg border-b border-border">
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">User</th>
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Judul Plan</th>
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Big Rock</th>
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Plan</th>
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Realisasi</th>
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Temuan</th>
                            <th class="text-right px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($rows as $row)
                            <tr class="hover:bg-app-bg transition-colors {{ $row['has_finding'] ? 'bg-danger-bg/20' : '' }}">
                                <td class="px-4 py-3.5 font-medium text-text">{{ $row['user'] }}</td>
                                <td class="px-4 py-3.5 text-text max-w-[260px] truncate">{{ $row['title'] }}</td>
                                <td class="px-4 py-3.5"><span class="badge-primary">{{ $row['big_rock'] }}</span></td>
                                <td class="px-4 py-3.5"><x-ui.status-badge :status="$row['plan_status']" /></td>
                                <td class="px-4 py-3.5"><x-ui.status-badge :status="$row['realization_status']" /></td>
                                <td class="px-4 py-3.5">
                                    @if($row['severity'])
                                        <div class="flex items-center gap-2">
                                            <x-ui.severity-badge :severity="$row['severity']" />
                                            <span class="text-sm text-muted">{{ $row['finding_count'] }}x</span>
                                        </div>
                                    @else
                                        <span class="text-sm text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <button
                                        type="button"
                                        class="btn-secondary px-4"
                                        wire:click="openDetail({{ $row['user_id'] }})"
                                        wire:target="openDetail({{ $row['user_id'] }})"
                                        wire:loading.attr="disabled"
                                    >
                                        <span wire:loading.remove wire:target="openDetail({{ $row['user_id'] }})">Lihat</span>
                                        <span wire:loading wire:target="openDetail({{ $row['user_id'] }})">Membuka...</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10">
                                    <x-ui.empty-state title="Belum ada data di tanggal ini" icon="calendar" class="py-6" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-3">
            @forelse($rows as $row)
                <x-ui.card class="{{ $row['has_finding'] ? 'border-danger/20 bg-danger-bg/15' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-text truncate">{{ $row['title'] }}</p>
                            <p class="text-sm text-muted mt-1">{{ $row['user'] }} · {{ $row['date'] }}</p>
                        </div>
                        @if($row['severity'])
                            <x-ui.severity-badge :severity="$row['severity']" />
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-1.5 text-sm mt-2">
                        <span class="badge-primary">{{ $row['big_rock'] }}</span>
                        <span class="badge-muted">{{ $row['roadmap'] }}</span>
                    </div>

                    <div class="flex gap-3 mt-2 text-sm">
                        <span class="text-muted">Plan:</span> <x-ui.status-badge :status="$row['plan_status']" />
                        <span class="text-muted ml-2">Real:</span> <x-ui.status-badge :status="$row['realization_status']" />
                    </div>

                    <div class="mt-3">
                        <button
                            type="button"
                            class="btn-secondary w-full"
                            wire:click="openDetail({{ $row['user_id'] }})"
                            wire:target="openDetail({{ $row['user_id'] }})"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="openDetail({{ $row['user_id'] }})">Lihat Detail</span>
                            <span wire:loading wire:target="openDetail({{ $row['user_id'] }})">Membuka...</span>
                        </button>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty-state title="Belum ada data di tanggal ini" icon="calendar" class="py-10" />
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
                    <div>
                        <h3 class="font-semibold text-text">Detail Entry</h3>
                        <p class="text-sm text-muted mt-0.5">{{ $selected['user'] ?? '-' }} · {{ $selected['date'] ?? '-' }}</p>
                    </div>
                    <button type="button" wire:click="closeDrawer" class="btn-secondary px-4">Tutup</button>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-sm text-muted">Plan Status</p>
                            <x-ui.status-badge :status="$selected['plan_status'] ?? 'missing'" />
                            @if(!empty($selected['plan_submitted_at']))
                                <p class="text-sm text-muted mt-1">Submit: {{ $selected['plan_submitted_at'] }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-muted">Realisasi Status</p>
                            <x-ui.status-badge :status="$selected['realization_status'] ?? 'missing'" />
                            @if(!empty($selected['realization_submitted_at']))
                                <p class="text-sm text-muted mt-1">Submit: {{ $selected['realization_submitted_at'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide">Temuan</p>
                        @if(empty($selectedFindings) && (($selected['plan_status'] ?? '') !== 'missing') && (($selected['realization_status'] ?? '') !== 'missing'))
                            <p class="text-sm text-muted">Tidak ada temuan di tanggal ini.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($selectedFindings as $f)
                                    <div class="p-3 rounded-xl border border-border">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-sm font-medium text-text">{{ $f['title'] }}</p>
                                            <x-ui.severity-badge :severity="$f['severity']" />
                                        </div>
                                        @if(!empty($f['description']))
                                            <p class="text-sm text-muted mt-1 whitespace-pre-line">{{ $f['description'] }}</p>
                                        @endif
                                    </div>
                                @endforeach

                                @if(($selected['plan_status'] ?? '') === 'missing' || ($selected['realization_status'] ?? '') === 'missing')
                                    <div class="p-3 rounded-xl border border-danger/20 bg-danger-bg/15">
                                        <p class="text-sm text-danger font-medium">Laporan harian tidak lengkap (missing)</p>
                                        <p class="text-sm text-muted mt-1">Plan atau realisasi belum terisi di hari ini.</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide">Item Plan & Realisasi</p>
                        @if(empty($selectedItems))
                            <p class="text-sm text-muted">Tidak ada item plan (atau entry belum dibuat).</p>
                        @else
                            <div class="space-y-3">
                                @foreach($selectedItems as $it)
                                    <x-ui.card>
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-sm font-semibold text-text">{{ $it['title'] }}</p>
                                            <x-ui.status-badge :status="$it['realization_status']" />
                                        </div>

                                        <div class="flex flex-wrap items-center gap-1.5 text-sm mt-1.5">
                                            <span class="badge-primary">{{ $it['big_rock'] }}</span>
                                            <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            <span class="badge-muted">{{ $it['roadmap'] }}</span>
                                        </div>

                                        <div class="mt-3 space-y-2">
                                            <div>
                                                <p class="text-sm text-muted">Durasi Plan:
                                                    @php($mins = (int) ($it['plan_duration_minutes'] ?? 0))
                                                    <span class="text-text">
                                                        @if($mins > 0 && $mins % 60 === 0)
                                                            {{ (int) ($mins / 60) }} jam
                                                        @elseif($mins > 0)
                                                            {{ $mins }} menit
                                                        @else
                                                            —
                                                        @endif
                                                    </span>
                                                </p>
                                                <p class="text-sm font-semibold text-muted uppercase tracking-wide">Deskripsi Rencana</p>
                                                <p class="text-sm text-text whitespace-pre-line">{{ $it['plan_text'] ?: '-' }}</p>
                                            </div>
                                            @if(!empty($it['plan_relation_reason']))
                                                <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-1">Alasan Terkait Big Rock</p>
                                                    <p class="text-sm text-text whitespace-pre-line">{{ $it['plan_relation_reason'] }}</p>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-3 space-y-2">
                                            <p class="text-sm font-semibold text-muted uppercase tracking-wide">Realisasi</p>
                                            <p class="text-sm text-muted">Durasi Realisasi:
                                                @php($mins = (int) ($it['realization_duration_minutes'] ?? 0))
                                                <span class="text-text">
                                                    @if($mins > 0 && $mins % 60 === 0)
                                                        {{ (int) ($mins / 60) }} jam
                                                    @elseif($mins > 0)
                                                        {{ $mins }} menit
                                                    @else
                                                        —
                                                    @endif
                                                </span>
                                            </p>
                                            <p class="text-sm text-text whitespace-pre-line">{{ $it['realization_text'] ?: 'Belum ada isi realisasi.' }}</p>
                                            @if(!empty($it['realization_reason']))
                                                <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-1">Alasan / Kendala</p>
                                                    <p class="text-sm text-text whitespace-pre-line">{{ $it['realization_reason'] }}</p>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-3 space-y-2">
                                            <p class="text-sm font-semibold text-muted uppercase tracking-wide">Lampiran</p>
                                            @if(empty($it['attachments']))
                                                <p class="text-sm text-muted">Tidak ada lampiran.</p>
                                            @else
                                                <div class="space-y-2">
                                                    @foreach($it['attachments'] as $a)
                                                        <div class="flex items-start justify-between gap-3 p-3 rounded-xl border border-border">
                                                            <div class="min-w-0">
                                                                <p class="text-sm font-medium text-text truncate">{{ $a['name'] }}</p>
                                                                <p class="text-sm text-muted mt-0.5">
                                                                    @if($a['size_kb']) {{ $a['size_kb'] }} KB @else - @endif
                                                                </p>
                                                            </div>
                                                            @if(!empty($a['url']))
                                                                <a href="{{ $a['url'] }}" target="_blank" class="btn-secondary px-4">Buka</a>
                                                            @else
                                                                <span class="text-sm text-muted">-</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </x-ui.card>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
