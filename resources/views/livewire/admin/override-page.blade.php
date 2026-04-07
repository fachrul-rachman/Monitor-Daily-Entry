{{--
    Admin Override Page
    Route: /admin/override
    Component: App\Livewire\Admin\OverridePage
--}}

<x-layouts.app title="Override Entry">
    @php
        $entries = [
            ['id' => 1, 'user' => 'Budi Santoso', 'division' => 'Operasional', 'date' => '7 Jul 2025', 'type' => 'Plan', 'title' => 'Review dokumen procurement', 'big_rock' => 'Optimasi Proses', 'status' => 'submitted'],
            ['id' => 2, 'user' => 'Ahmad Fauzi', 'division' => 'IT', 'date' => '7 Jul 2025', 'type' => 'Realisasi', 'title' => 'Deploy patch server utama', 'big_rock' => 'Stabilitas Sistem', 'status' => 'finished'],
            ['id' => 3, 'user' => 'Siti Rahayu', 'division' => 'Keuangan', 'date' => '6 Jul 2025', 'type' => 'Plan', 'title' => 'Rekonsiliasi bank bulan Juni', 'big_rock' => 'Akurasi Laporan', 'status' => 'blocked'],
        ];
    @endphp

    <x-ui.page-header title="Override Entry" description="Ubah entry plan atau realisasi yang sudah disubmit" />

    {{-- Warning banner --}}
    <div class="bg-danger-bg border border-danger/20 rounded-xl p-4 mb-6 flex items-start gap-3">
        <svg class="w-5 h-5 text-danger shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        <p class="text-sm text-danger font-medium">Perhatian: Semua perubahan override dicatat dalam log audit dan tidak bisa dihapus.</p>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <select class="input w-48"><option value="">Semua User</option><option>Budi Santoso</option><option>Ahmad Fauzi</option><option>Siti Rahayu</option></select>
        <select class="input w-40"><option value="">Semua Divisi</option><option>Operasional</option><option>IT</option><option>Keuangan</option></select>
        <input type="date" class="input w-40" />
        <select class="input w-40"><option value="">Semua Tipe</option><option>Plan</option><option>Realisasi</option></select>
    </div>

    <div x-data="{ overrideOpen: false, selectedEntry: null }">
        {{-- Entry list --}}
        <div class="hidden md:block">
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-app-bg border-b border-border">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">User</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Divisi</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Tanggal</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Tipe</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Judul</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Status</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($entries as $entry)
                            <tr class="hover:bg-app-bg transition-colors">
                                <td class="px-4 py-3.5 font-medium text-text">{{ $entry['user'] }}</td>
                                <td class="px-4 py-3.5 text-muted">{{ $entry['division'] }}</td>
                                <td class="px-4 py-3.5 text-text">{{ $entry['date'] }}</td>
                                <td class="px-4 py-3.5"><span class="badge-primary">{{ $entry['type'] }}</span></td>
                                <td class="px-4 py-3.5 text-text">{{ $entry['title'] }}</td>
                                <td class="px-4 py-3.5"><x-ui.status-badge :status="$entry['status']" /></td>
                                <td class="px-4 py-3.5 text-right">
                                    <button
                                        @click="overrideOpen = true; selectedEntry = {{ json_encode($entry) }}"
                                        class="text-sm text-primary font-medium hover:underline"
                                    >Override</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="block md:hidden space-y-3">
            @foreach($entries as $entry)
                <x-ui.card class="cursor-pointer" @click="overrideOpen = true; selectedEntry = {{ json_encode($entry) }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-text">{{ $entry['user'] }}</p>
                            <p class="text-xs text-muted">{{ $entry['division'] }} · {{ $entry['date'] }}</p>
                        </div>
                        <x-ui.status-badge :status="$entry['status']" />
                    </div>
                    <p class="text-sm text-text mt-2">{{ $entry['title'] }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="badge-primary">{{ $entry['type'] }}</span>
                        <span class="badge-muted">{{ $entry['big_rock'] }}</span>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        {{-- Override Slide Over Panel --}}
        <div x-show="overrideOpen" class="fixed inset-0 z-40 flex justify-end" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="overrideOpen = false"></div>
            <div class="relative w-full max-w-lg bg-surface h-full overflow-y-auto shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0">
                <div class="p-5 border-b border-border flex items-center justify-between sticky top-0 bg-surface z-10">
                    <h3 class="font-semibold text-text">Override Entry</h3>
                    <button @click="overrideOpen = false" class="text-muted hover:text-text">✕</button>
                </div>
                <div class="p-5 space-y-5">
                    {{-- Entry info --}}
                    <div class="bg-app-bg rounded-xl p-4">
                        <p class="text-xs text-muted mb-1">Entry yang dipilih</p>
                        <p class="text-sm font-medium text-text" x-text="selectedEntry?.title"></p>
                        <p class="text-xs text-muted mt-1" x-text="(selectedEntry?.user || '') + ' · ' + (selectedEntry?.division || '') + ' · ' + (selectedEntry?.date || '')"></p>
                    </div>

                    {{-- Original vs Edit --}}
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-text">Ubah Field</h4>

                        {{-- Title --}}
                        <div>
                            <label class="label">Judul</label>
                            <div class="bg-app-bg rounded-lg px-3 py-2.5 text-sm text-muted mb-2 border border-border">
                                <span class="text-xs text-muted block mb-0.5">Original:</span>
                                <span x-text="selectedEntry?.title"></span>
                            </div>
                            {{-- TODO: wire:model="editValues.title" --}}
                            <input type="text" class="input border-primary" :value="selectedEntry?.title" placeholder="Nilai baru..." />
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="label">Status</label>
                            <div class="bg-app-bg rounded-lg px-3 py-2.5 text-sm text-muted mb-2 border border-border">
                                <span class="text-xs text-muted block mb-0.5">Original:</span>
                                <span x-text="selectedEntry?.status"></span>
                            </div>
                            <select class="input border-primary">
                                <option value="submitted">Submitted</option>
                                <option value="finished">Selesai</option>
                                <option value="in_progress">Sedang Berjalan</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>

                        {{-- Alasan Override --}}
                        <div>
                            <label class="label">Alasan Override <span class="text-danger">*</span></label>
                            {{-- TODO: wire:model="overrideReason" --}}
                            <textarea class="input min-h-[100px]" placeholder="Jelaskan alasan override ini..." rows="3"></textarea>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-2" x-data="{ showConfirm: false }">
                        <button @click="overrideOpen = false" class="btn-secondary flex-1">Batal</button>
                        <button @click="showConfirm = true" class="btn-primary flex-1">Simpan Override</button>

                        <x-ui.confirmation-modal
                            title="Konfirmasi Override"
                            message="Override ini akan dicatat dalam log. Lanjutkan?"
                            confirm-label="Ya, Override"
                            confirm-action="saveOverride"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
