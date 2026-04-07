{{--
    Admin Leave Page
    Route: /admin/absence-leave
    Component: App\Livewire\Admin\LeavePage
--}}

<x-layouts.app title="Cuti & Izin">
    @php
        $summaryCount = ['pending' => 3, 'approved' => 12, 'rejected' => 2];
        $leaveRequests = [
            ['id' => 1, 'user' => 'Budi Santoso', 'division' => 'Operasional', 'type' => 'Cuti Tahunan', 'date' => '8-10 Jul 2025', 'reason' => 'Acara keluarga di luar kota. Sudah koordinasi dengan tim.', 'status' => 'pending', 'submitted' => '5 Jul 2025'],
            ['id' => 2, 'user' => 'Siti Rahayu', 'division' => 'Keuangan', 'type' => 'Izin Sakit', 'date' => '7 Jul 2025', 'reason' => 'Demam tinggi, surat dokter terlampir.', 'status' => 'approved', 'submitted' => '7 Jul 2025'],
            ['id' => 3, 'user' => 'Ahmad Fauzi', 'division' => 'IT', 'type' => 'Cuti Tahunan', 'date' => '9-11 Jul 2025', 'reason' => 'Perjalanan dinas ke cabang Surabaya.', 'status' => 'pending', 'submitted' => '6 Jul 2025'],
            ['id' => 4, 'user' => 'Dewi Lestari', 'division' => 'Marketing', 'type' => 'Izin Pribadi', 'date' => '3 Jul 2025', 'reason' => 'Urusan administratif ke kantor pemerintah.', 'status' => 'rejected', 'submitted' => '2 Jul 2025'],
            ['id' => 5, 'user' => 'Rudi Hermawan', 'division' => 'Operasional', 'type' => 'Cuti Tahunan', 'date' => '14-18 Jul 2025', 'reason' => 'Liburan keluarga.', 'status' => 'pending', 'submitted' => '5 Jul 2025'],
        ];
    @endphp

    <x-ui.page-header title="Cuti & Izin" description="Kelola permintaan cuti dan izin karyawan" />

    {{-- Summary chips --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <span class="badge-warning">Pending: {{ $summaryCount['pending'] }}</span>
        <span class="badge-success">Disetujui: {{ $summaryCount['approved'] }}</span>
        <span class="badge-danger">Ditolak: {{ $summaryCount['rejected'] }}</span>
    </div>

    {{-- Filter bar --}}
    <div class="mb-6" x-data="{ filterOpen: false }">
        <div class="hidden md:flex gap-3 flex-wrap items-center">
            <input type="date" class="input w-40" />
            <span class="text-muted">—</span>
            <input type="date" class="input w-40" />
            <select class="input w-36">
                <option value="">Semua Status</option>
                <option>Pending</option>
                <option>Disetujui</option>
                <option>Ditolak</option>
            </select>
            <select class="input w-40">
                <option value="">Semua Divisi</option>
                <option>Operasional</option>
                <option>Keuangan</option>
                <option>IT</option>
                <option>Marketing</option>
            </select>
            <select class="input w-36">
                <option value="">Semua Tipe</option>
                <option>Cuti Tahunan</option>
                <option>Izin Sakit</option>
                <option>Izin Pribadi</option>
            </select>
            <button class="text-sm text-muted hover:text-text">Reset</button>
        </div>
        <div class="flex gap-2 items-center md:hidden">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" placeholder="Cari..." class="input pl-9">
            </div>
            <button @click="filterOpen = true" class="btn-secondary gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
        </div>
        {{-- Mobile bottom sheet - simplified --}}
        <div x-show="filterOpen" class="fixed inset-0 z-40 md:hidden" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="filterOpen = false"></div>
            <div class="absolute bottom-0 left-0 right-0 bg-surface rounded-t-2xl p-5 space-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-text">Filter</p>
                    <button @click="filterOpen = false" class="text-muted text-lg">✕</button>
                </div>
                <div><label class="label">Status</label><select class="input"><option value="">Semua</option><option>Pending</option><option>Disetujui</option><option>Ditolak</option></select></div>
                <div><label class="label">Divisi</label><select class="input"><option value="">Semua</option><option>Operasional</option><option>Keuangan</option><option>IT</option></select></div>
                <div><label class="label">Tipe</label><select class="input"><option value="">Semua</option><option>Cuti Tahunan</option><option>Izin Sakit</option></select></div>
                <button @click="filterOpen = false" class="btn-primary w-full">Terapkan</button>
            </div>
        </div>
    </div>

    {{-- Detail slide-over panel --}}
    <div x-data="{ detailOpen: false, selectedLeave: null }">

        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-app-bg border-b border-border">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">User</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Divisi</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Tipe</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Tanggal</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Alasan</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Status</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($leaveRequests as $leave)
                            <tr class="hover:bg-app-bg transition-colors cursor-pointer" @click="detailOpen = true; selectedLeave = {{ json_encode($leave) }}">
                                <td class="px-4 py-3.5 font-medium text-text">{{ $leave['user'] }}</td>
                                <td class="px-4 py-3.5 text-muted">{{ $leave['division'] }}</td>
                                <td class="px-4 py-3.5 text-text">{{ $leave['type'] }}</td>
                                <td class="px-4 py-3.5 text-text">{{ $leave['date'] }}</td>
                                <td class="px-4 py-3.5 text-muted max-w-[200px] truncate">{{ $leave['reason'] }}</td>
                                <td class="px-4 py-3.5"><x-ui.status-badge :status="$leave['status']" /></td>
                                <td class="px-4 py-3.5 text-right" @click.stop>
                                    @if($leave['status'] === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- TODO: wire:click="approve({{ $leave['id'] }})" --}}
                                            <button class="text-xs text-success font-medium hover:underline">Setujui</button>
                                            {{-- TODO: wire:click="reject({{ $leave['id'] }})" --}}
                                            <button class="text-xs text-danger font-medium hover:underline">Tolak</button>
                                        </div>
                                    @else
                                        <button class="text-xs text-primary font-medium hover:underline" @click="detailOpen = true; selectedLeave = {{ json_encode($leave) }}">Detail</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="block md:hidden space-y-3">
            @forelse($leaveRequests as $leave)
                <x-ui.card class="cursor-pointer" @click="detailOpen = true; selectedLeave = {{ json_encode($leave) }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-text">{{ $leave['user'] }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ $leave['division'] }} · {{ $leave['type'] }}</p>
                        </div>
                        <x-ui.status-badge :status="$leave['status']" />
                    </div>
                    <p class="text-sm text-muted mt-2">{{ $leave['date'] }}</p>
                    <p class="text-xs text-muted mt-1 line-clamp-2">{{ $leave['reason'] }}</p>
                    @if($leave['status'] === 'pending')
                        <div class="mt-3 pt-3 border-t border-border flex gap-2" @click.stop>
                            <button class="text-sm text-success font-medium">Setujui</button>
                            <button class="text-sm text-danger font-medium ml-auto">Tolak</button>
                        </div>
                    @endif
                </x-ui.card>
            @empty
                <x-ui.empty-state title="Tidak ada permintaan cuti pada periode ini" icon="calendar" />
            @endforelse
        </div>

        {{-- Detail Slide Over Panel --}}
        <div x-show="detailOpen" class="fixed inset-0 z-40 flex justify-end" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="detailOpen = false"></div>
            <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0">
                <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface z-10">
                    <h3 class="font-semibold text-text">Detail Permintaan</h3>
                    <button @click="detailOpen = false" class="text-muted hover:text-text">✕</button>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-muted">Nama</p><p class="text-sm font-medium text-text" x-text="selectedLeave?.user"></p></div>
                        <div><p class="text-xs text-muted">Divisi</p><p class="text-sm font-medium text-text" x-text="selectedLeave?.division"></p></div>
                        <div><p class="text-xs text-muted">Tipe</p><p class="text-sm font-medium text-text" x-text="selectedLeave?.type"></p></div>
                        <div><p class="text-xs text-muted">Tanggal</p><p class="text-sm font-medium text-text" x-text="selectedLeave?.date"></p></div>
                    </div>
                    <div>
                        <p class="text-xs text-muted mb-1">Alasan</p>
                        <p class="text-sm text-text" x-text="selectedLeave?.reason"></p>
                    </div>
                    <div>
                        <p class="text-xs text-muted mb-1">Diajukan</p>
                        <p class="text-sm text-text" x-text="selectedLeave?.submitted"></p>
                    </div>

                    {{-- Actions for pending --}}
                    <template x-if="selectedLeave?.status === 'pending'">
                        <div class="flex gap-3 pt-4 border-t border-border">
                            {{-- TODO: wire:click + confirmation --}}
                            <button class="btn-primary flex-1">Setujui</button>
                            <button class="btn-danger flex-1">Tolak</button>
                        </div>
                    </template>

                    {{-- Audit trail dummy --}}
                    <div class="pt-4 border-t border-border">
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Riwayat</p>
                        <div class="space-y-3">
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></div>
                                    <div class="w-px flex-1 bg-border mt-1"></div>
                                </div>
                                <div class="pb-3">
                                    <p class="text-sm font-medium text-text">Permintaan diajukan</p>
                                    <p class="text-xs text-muted mt-0.5" x-text="(selectedLeave?.user || '') + ' · ' + (selectedLeave?.submitted || '')"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
