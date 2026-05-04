<div>
    <x-ui.page-header title="ISO Monitor" description="Pantau pengisian Plan/Realisasi dan temuan (HoD + Manager)" />

    <div class="flex flex-wrap gap-2 mb-6">
        <span class="badge-danger">Missing: {{ $summary['missing'] ?? 0 }}</span>
        <span class="badge-warning">Late: {{ $summary['late'] ?? 0 }}</span>
        <span class="badge-danger">High: {{ $summary['high'] ?? 0 }}</span>
        <span class="badge-warning">Medium: {{ $summary['medium'] ?? 0 }}</span>
        <span class="badge-muted">Periode: {{ $fromLabel }} – {{ $toLabel }}</span>
    </div>

    <x-ui.card>
        <form class="flex flex-wrap items-end gap-3" wire:submit.prevent="applyFilters">
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
                <label class="label">Divisi</label>
                <select class="input" wire:model="division">
                    <option value="">Semua</option>
                    @foreach($divisionOptions as $d)
                        <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-52">
                <label class="label">Status</label>
                <select class="input" wire:model="status">
                    <option value="">Semua</option>
                    <option value="missing">Missing</option>
                    <option value="late">Late</option>
                    <option value="finding_high">High finding</option>
                    <option value="finding_medium">Medium finding</option>
                    <option value="ok">OK</option>
                </select>
            </div>
            <div class="flex-1 min-w-[220px]">
                <label class="label">Cari</label>
                <input type="text" class="input" placeholder="Nama / email..." wire:model.debounce.400ms="search" />
            </div>
            <div class="flex items-center gap-2 pb-1">
                <button type="submit" class="btn-secondary px-4" wire:target="applyFilters" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="applyFilters">Terapkan</span>
                    <span wire:loading wire:target="applyFilters">Memuat…</span>
                </button>
                <button type="button" class="text-sm text-muted hover:text-text" wire:click="resetFilters">Reset</button>
            </div>
        </form>
    </x-ui.card>

    <div class="mt-6" x-data="{ drawerOpen: @entangle('drawerOpen') }">
        <div class="overflow-x-auto rounded-xl border border-border bg-surface">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-app-bg border-b border-border">
                        <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">User</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Divisi</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Missing</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Late</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Planning</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Realisasi</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Findings</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Big Rock / Roadmap</th>
                        <th class="text-right px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($rows as $r)
                        <tr class="hover:bg-app-bg transition-colors">
                            <td class="px-4 py-3.5">
                                <p class="font-medium text-text">{{ $r['name'] }}</p>
                                <p class="text-sm text-muted">{{ $r['label'] }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-text">{{ $r['division'] }}</td>
                            <td class="px-4 py-3.5">
                                <span class="{{ ($r['missing'] ?? 0) > 0 ? 'badge-danger' : 'badge-muted' }}">{{ $r['missing'] ?? 0 }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="{{ (($r['late_plan'] ?? 0) + ($r['late_real'] ?? 0)) > 0 ? 'badge-warning' : 'badge-muted' }}">
                                    {{ ($r['late_plan'] ?? 0) + ($r['late_real'] ?? 0) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="{{ ($r['planning'] ?? 0) > 0 ? 'badge-primary' : 'badge-muted' }}">{{ $r['planning'] ?? 0 }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="{{ ($r['realization'] ?? 0) > 0 ? 'badge-primary' : 'badge-muted' }}">{{ $r['realization'] ?? 0 }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-text">
                                <span class="badge-danger">High: {{ $r['find_high'] ?? 0 }}</span>
                                <span class="badge-warning ml-1">Med: {{ $r['find_med'] ?? 0 }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-text">
                                <span class="badge-primary">{{ $r['total_big_rocks'] ?? 0 }} BR</span>
                                <span class="badge-muted ml-1">{{ $r['total_roadmaps'] ?? 0 }} RM</span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <button type="button" class="btn-secondary px-4" wire:click="openDetail({{ $r['id'] }})">Buka</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-muted">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>

        {{-- Drawer --}}
        <div x-show="drawerOpen" class="fixed inset-0 z-40 flex justify-end" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="$wire.closeDrawer()"></div>
            <div class="relative w-full max-w-2xl bg-surface h-full overflow-y-auto shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0">
                <div class="p-5 border-b border-border flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-text truncate">{{ $selectedUser['name'] ?? '—' }}</p>
                        <p class="text-sm text-muted truncate">
                            {{ strtoupper($selectedUser['role'] ?? '') }} · {{ $selectedUser['division'] ?? '—' }} · {{ $selectedUser['from'] ?? '' }} – {{ $selectedUser['to'] ?? '' }}
                        </p>
                    </div>
                    <button type="button" class="btn-secondary px-4" @click="$wire.closeDrawer()">Tutup</button>
                </div>

                <div class="p-5 space-y-6">
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" class="text-sm text-primary hover:underline" wire:click="expandAll">Buka semua</button>
                        <button type="button" class="text-sm text-muted hover:text-text" wire:click="collapseAll">Tutup semua</button>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Big Rock &amp; Roadmap</p>
                        @if(empty($selectedBigRocks))
                            <p class="text-sm text-muted">Belum ada Big Rock.</p>
                        @else
                            <div class="space-y-3">
                                @foreach($selectedBigRocks as $br)
                                    <div class="p-3 rounded-xl border border-border">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-text whitespace-normal break-words">{{ $br['title'] }}</p>
                                                <p class="text-sm text-muted mt-0.5">
                                                    {{ strtoupper($br['status'] ?? 'active') }} · {{ $br['start'] ?? '—' }} – {{ $br['end'] ?? '—' }} · Progress {{ $br['progress'] ?? 0 }}%
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="badge-primary">{{ $br['progress'] ?? 0 }}%</span>
                                                <button type="button" class="btn-secondary px-4" wire:click="toggleBigRock({{ (int) ($br['id'] ?? 0) }})">Detail</button>
                                            </div>
                                        </div>

                                        @if(in_array((int) ($br['id'] ?? 0), $openBigRockIds ?? [], true))
                                            @if(!empty($br['description']))
                                                <div class="mt-3 pt-3 border-t border-border">
                                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Deskripsi</p>
                                                    <p class="text-sm text-text whitespace-pre-line">{{ $br['description'] }}</p>
                                                </div>
                                            @endif

                                            <div class="mt-3 pt-3 border-t border-border">
                                                <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Roadmap</p>
                                                @if(empty($br['roadmaps']))
                                                    <p class="text-sm text-muted">Tidak ada roadmap.</p>
                                                @else
                                                    <div class="space-y-2">
                                                        @foreach($br['roadmaps'] as $rm)
                                                            <div class="flex items-center justify-between gap-3 p-2 rounded-lg bg-app-bg border border-border">
                                                                <p class="text-sm text-text whitespace-normal break-words">{{ $rm['title'] }}</p>
                                                                <x-ui.status-badge :status="$rm['status']" />
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if(false)
                    <div>
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Ringkas per hari</p>
                        @if(empty($selectedDays))
                            <p class="text-sm text-muted">Tidak ada entry di periode ini.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($selectedDays as $d)
                                    <div class="p-3 rounded-xl border border-border">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-sm font-medium text-text">{{ $d['date'] }}</p>
                                            <div class="flex items-center gap-2">
                                                <x-ui.status-badge :status="$d['plan_status']" />
                                                <x-ui.status-badge :status="$d['real_status']" />
                                            </div>
                                        </div>
                                        <p class="text-sm text-muted mt-1">
                                            Plan submit: {{ $d['plan_submitted_at'] ?? '—' }} · Real submit: {{ $d['real_submitted_at'] ?? '—' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif

                    <div>
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Item Planning</p>
                        @if(empty($selectedPlanItems))
                            <p class="text-sm text-muted">Tidak ada item planning.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($selectedPlanItems as $it)
                                    @php($id = (int) ($it['id'] ?? 0))
                                    <div class="p-3 rounded-xl border border-border">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-text whitespace-normal break-words">{{ $it['title'] }}</p>
                                                <p class="text-sm text-muted mt-0.5 whitespace-normal break-words">
                                                    <span class="font-medium text-text">{{ $it['date'] }}</span><br>
                                                    <span>Big Rock: {{ $it['big_rock'] }}</span><br>
                                                    <span>Roadmap: {{ $it['roadmap'] }}</span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <x-ui.status-badge :status="$it['plan_status']" />
                                                <button type="button" class="btn-secondary px-4" wire:click="togglePlanItem({{ $id }})">Detail</button>
                                            </div>
                                        </div>
                                        <p class="text-sm text-muted mt-1">
                                            Durasi:
                                            @php($m=(int)($it['plan_minutes'] ?? 0))
                                            @if($m > 0 && $m % 60 === 0) {{ (int)($m/60) }} jam @elseif($m > 0) {{ $m }} menit @else â€” @endif
                                        </p>

                                        @if(in_array($id, $openPlanItemIds ?? [], true))
                                            <div class="mt-3 pt-3 border-t border-border space-y-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Deskripsi Planning</p>
                                                    @if(trim((string)($it['plan_text'] ?? '')) !== '')
                                                        <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                                            <p class="text-sm text-text whitespace-pre-line">{{ $it['plan_text'] }}</p>
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-muted">Tidak ada deskripsi.</p>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Alasan Terkait Big Rock</p>
                                                    @if(trim((string)($it['plan_relation_reason'] ?? '')) !== '')
                                                        <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                                            <p class="text-sm text-text whitespace-pre-line">{{ $it['plan_relation_reason'] }}</p>
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-muted">â€”</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Item Realisasi</p>
                        @if(empty($selectedRealizationItems))
                            <p class="text-sm text-muted">Tidak ada item realisasi.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($selectedRealizationItems as $it)
                                    @php($id = (int) ($it['id'] ?? 0))
                                    <div class="p-3 rounded-xl border border-border">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-text whitespace-normal break-words">{{ $it['title'] }}</p>
                                                <p class="text-sm text-muted mt-0.5 whitespace-normal break-words">
                                                    <span class="font-medium text-text">{{ $it['date'] }}</span><br>
                                                    <span>Big Rock: {{ $it['big_rock'] }}</span><br>
                                                    <span>Roadmap: {{ $it['roadmap'] }}</span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <x-ui.status-badge :status="$it['real_status']" />
                                                <button type="button" class="btn-secondary px-4" wire:click="toggleRealItem({{ $id }})">Detail</button>
                                            </div>
                                        </div>
                                        <p class="text-sm text-muted mt-1">
                                            Durasi:
                                            @php($m=(int)($it['real_minutes'] ?? 0))
                                            @if($m > 0 && $m % 60 === 0) {{ (int)($m/60) }} jam @elseif($m > 0) {{ $m }} menit @else â€” @endif
                                        </p>

                                        @if(in_array($id, $openRealItemIds ?? [], true))
                                            <div class="mt-3 pt-3 border-t border-border space-y-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Deskripsi Realisasi</p>
                                                    @if(trim((string)($it['realization_text'] ?? '')) !== '')
                                                        <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                                            <p class="text-sm text-text whitespace-pre-line">{{ $it['realization_text'] }}</p>
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-muted">Tidak ada isi realisasi.</p>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Alasan / Kendala</p>
                                                    @if(trim((string)($it['realization_reason'] ?? '')) !== '')
                                                        <div class="rounded-xl border border-border bg-app-bg px-4 py-3">
                                                            <p class="text-sm text-text whitespace-pre-line">{{ $it['realization_reason'] }}</p>
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-muted">â€”</p>
                                                    @endif
                                                </div>

                                                <div>
                                                    <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Lampiran</p>
                                                    @if(empty($it['attachments']))
                                                        <p class="text-sm text-muted">Tidak ada lampiran.</p>
                                                    @else
                                                        <div class="space-y-2">
                                                            @foreach($it['attachments'] as $a)
                                                                <div class="flex items-start justify-between gap-3 p-2 rounded-lg bg-app-bg border border-border">
                                                                    <div class="min-w-0">
                                                                        <p class="text-sm font-medium text-text break-words">{{ $a['name'] }}</p>
                                                                        <p class="text-sm text-muted mt-0.5">
                                                                            @if(!empty($a['size_kb'])) {{ $a['size_kb'] }} KB @else â€” @endif
                                                                        </p>
                                                                    </div>
                                                                    @if(!empty($a['url']))
                                                                        <a href="{{ $a['url'] }}" target="_blank" class="btn-secondary px-4">Buka</a>
                                                                    @else
                                                                        <span class="text-sm text-muted">â€”</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if(false)
                    <div>
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Item Plan/Realisasi</p>
                        @if(empty($selectedItems))
                            <p class="text-sm text-muted">Tidak ada item.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($selectedItems as $it)
                                    <div class="p-3 rounded-xl border border-border">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-text whitespace-normal break-words">{{ $it['title'] }}</p>
                                                <p class="text-sm text-muted mt-0.5 whitespace-normal break-words">
                                                    <span class="font-medium text-text">{{ $it['date'] }}</span><br>
                                                    <span>Big Rock: {{ $it['big_rock'] }}</span><br>
                                                    <span>Roadmap: {{ $it['roadmap'] }}</span>
                                                </p>
                                            </div>
                                            <x-ui.status-badge :status="$it['real_status']" />
                                        </div>
                                        <p class="text-sm text-muted mt-1">
                                            Plan:
                                            @php($m=(int)($it['plan_minutes'] ?? 0))
                                            @if($m > 0 && $m % 60 === 0) {{ (int)($m/60) }} jam @elseif($m > 0) {{ $m }} menit @else — @endif
                                            · Real:
                                            @php($m=(int)($it['real_minutes'] ?? 0))
                                            @if($m > 0 && $m % 60 === 0) {{ (int)($m/60) }} jam @elseif($m > 0) {{ $m }} menit @else — @endif
                                        </p>

                                        @if(!empty($it['attachments']))
                                            <div class="mt-2 pt-2 border-t border-border">
                                                <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Lampiran</p>
                                                <div class="space-y-2">
                                                    @foreach($it['attachments'] as $a)
                                                        <div class="flex items-start justify-between gap-3 p-2 rounded-lg bg-app-bg border border-border">
                                                            <div class="min-w-0">
                                                                <p class="text-sm font-medium text-text break-words">{{ $a['name'] }}</p>
                                                                <p class="text-sm text-muted mt-0.5">
                                                                    @if(!empty($a['size_kb'])) {{ $a['size_kb'] }} KB @else — @endif
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
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-muted uppercase tracking-wide mb-2">Findings</p>
                        @if(empty($selectedFindings))
                            <p class="text-sm text-muted">Tidak ada findings.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($selectedFindings as $f)
                                    <div class="p-3 rounded-xl border border-border">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-sm font-medium text-text">{{ $f['title'] }}</p>
                                            <x-ui.severity-badge :severity="$f['severity']" />
                                        </div>
                                        <p class="text-sm text-muted mt-1">{{ $f['date'] }} · {{ $f['type'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
