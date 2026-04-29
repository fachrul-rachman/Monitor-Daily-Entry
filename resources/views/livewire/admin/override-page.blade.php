<div>
    <x-ui.page-header
        title="Override Entry"
        description="Ubah status laporan plan/realisasi jika ada kondisi khusus. Semua override akan tercatat."
    />

    {{-- Warning banner --}}
    <div class="mb-6 bg-danger-bg border border-danger/30 rounded-xl p-4">
        <p class="text-sm font-semibold text-danger">Perhatian</p>
        <p class="text-sm text-danger mt-1">
            Semua perubahan override dicatat dalam log audit dan tidak bisa dihapus. Gunakan hanya untuk kondisi yang benar-benar diperlukan.
        </p>
    </div>

    {{-- Filters --}}
    <div class="mb-6" x-data="{ filterOpen: false, detailOpen: @entangle('drawerOpen').live }">
        {{-- Desktop --}}
        <form class="hidden md:flex gap-3 flex-wrap items-end" wire:submit.prevent="applyFilters">
            <div class="w-64">
                <label class="label">User</label>
                <input
                    type="text"
                    class="input"
                    placeholder="Cari nama/email..."
                    wire:model.live.debounce.400ms="search"
                />
            </div>

            <div class="w-48">
                <label class="label">Divisi</label>
                <select class="input" wire:model.live="division">
                    <option value="">Semua</option>
                    @foreach($divisionOptions as $d)
                        <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                    @endforeach
                </select>
            </div>

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

            <div class="w-44">
                <label class="label">Tipe</label>
                <select class="input" wire:model.live="type">
                    <option value="">Semua</option>
                    <option value="plan">Plan (yang bermasalah)</option>
                    <option value="realisasi">Realisasi (yang bermasalah)</option>
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
                <input type="text" placeholder="Cari nama/email..." class="input pl-9" wire:model.live.debounce.400ms="search" />
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

                <div>
                    <label class="label">Divisi</label>
                    <select class="input" wire:model.live="division">
                        <option value="">Semua</option>
                        @foreach($divisionOptions as $d)
                            <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                        @endforeach
                    </select>
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
                    <label class="label">Tipe</label>
                    <select class="input" wire:model.live="type">
                        <option value="">Semua</option>
                        <option value="plan">Plan (yang bermasalah)</option>
                        <option value="realisasi">Realisasi (yang bermasalah)</option>
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

        {{-- Entry list --}}
        <div class="mt-6 hidden md:block">
            <x-ui.card class="p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-app-bg text-muted text-sm uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Tanggal</th>
                                <th class="px-4 py-3 text-left">User</th>
                                <th class="px-4 py-3 text-left">Divisi</th>
                                <th class="px-4 py-3 text-left">Judul Plan</th>
                                <th class="px-4 py-3 text-left">Big Rock</th>
                                <th class="px-4 py-3 text-left">Roadmap</th>
                                <th class="px-4 py-3 text-left">Realisasi</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($items as $row)
                                <tr class="hover:bg-app-bg transition-colors">
                                    <td class="px-4 py-3.5 text-muted whitespace-nowrap">{{ $row['date'] }}</td>
                                    <td class="px-4 py-3.5 text-text font-medium">{{ $row['user'] }}</td>
                                    <td class="px-4 py-3.5 text-text">{{ $row['division'] }}</td>
                                    <td class="px-4 py-3.5 text-text max-w-[260px] truncate">{{ $row['plan_title'] }}</td>
                                    <td class="px-4 py-3.5 text-text max-w-[220px] truncate">{{ $row['big_rock'] }}</td>
                                    <td class="px-4 py-3.5 text-text max-w-[220px] truncate">{{ $row['roadmap'] }}</td>
                                    <td class="px-4 py-3.5"><x-ui.status-badge :status="$row['realization_status']" /></td>
                                    <td class="px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" class="btn-secondary px-3 py-2" wire:click="openOverride({{ $row['id'] }})">Override</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10">
                                        <x-ui.empty-state icon="document" title="Tidak ada entry pada periode ini" description="Coba ubah tanggal atau filter untuk menemukan entry yang perlu dibenahi." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        <div class="mt-6 md:hidden space-y-3">
            @forelse($items as $row)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-text">{{ $row['user'] }}</p>
                            <p class="text-sm text-muted mt-0.5">{{ $row['division'] }} · {{ $row['date'] }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-sm text-text font-medium">{{ $row['plan_title'] }}</p>
                        <p class="text-sm text-muted mt-1">Big Rock: {{ $row['big_rock'] }}</p>
                        <p class="text-sm text-muted mt-0.5">Roadmap: {{ $row['roadmap'] }}</p>
                        <div class="mt-2"><x-ui.status-badge :status="$row['realization_status']" /></div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn-secondary w-full" wire:click="openOverride({{ $row['id'] }})">Override</button>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty-state icon="document" title="Tidak ada entry pada periode ini" description="Coba ubah tanggal atau filter untuk menemukan entry yang perlu dibenahi." />
            @endforelse
        </div>

        <div class="mt-6">
            <x-ui.pagination :paginator="$entries" />
        </div>

        {{-- Override panel (drawer) --}}
        <div x-show="detailOpen" class="fixed inset-0 z-50" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="detailOpen = false; $wire.closeDrawer()"></div>

            <div
                class="absolute right-0 top-0 bottom-0 w-full md:w-[560px] bg-surface shadow-xl border-l border-border p-5 overflow-y-auto"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-text">Override Entry</p>
                        <p class="text-sm text-muted mt-0.5">Nilai lama ditampilkan sebagai referensi. Isi alasan override dengan jelas.</p>
                    </div>
                    <button type="button" class="btn-secondary px-3 py-2" @click="detailOpen = false; $wire.closeDrawer()">Tutup</button>
                </div>

                {{-- Selected info --}}
                <div class="mt-4 bg-app-bg border border-border rounded-xl p-4">
                    <p class="text-sm text-muted">Entry terpilih</p>
                    <p class="text-sm font-semibold text-text mt-1">{{ $selected['user'] ?? '-' }}</p>
                    <p class="text-sm text-muted mt-1">
                        {{ $selected['division'] ?? '-' }} · {{ $selected['date'] ?? '-' }}
                        @if(!empty($selected['email']))
                            · {{ $selected['email'] }}
                        @endif
                    </p>
                </div>

                {{-- Original vs new --}}
                <div class="mt-5 space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-text">Ubah status</p>
                        <p class="text-sm text-muted mt-0.5">Override ini untuk membetulkan detail plan/realisasi termasuk Big Rock, Roadmap, dan lampiran.</p>
                    </div>

                    {{-- Plan --}}
                    <div class="bg-surface border border-border rounded-xl p-4 space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-text">Plan</p>
                            <p class="text-sm text-muted mt-0.5">Rencana wajib terkait Big Rock milik user yang bersangkutan.</p>
                        </div>

                        <div>
                            <label class="label">Big Rock <span class="text-danger">*</span></label>
                            <select class="input border-primary" wire:model.live="editBigRockId">
                                <option value="">Pilih Big Rock...</option>
                                @foreach($bigRockOptions as $br)
                                    <option value="{{ $br['id'] }}">{{ $br['title'] }}</option>
                                @endforeach
                            </select>
                            @error('editBigRockId') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Roadmap</label>
                            <select class="input border-primary" wire:model.live="editRoadmapItemId" @disabled(empty($roadmapOptions))>
                                <option value="">(Opsional) Pilih Roadmap...</option>
                                @foreach($roadmapOptions as $rm)
                                    <option value="{{ $rm['id'] }}">{{ $rm['title'] }}</option>
                                @endforeach
                            </select>
                            @error('editRoadmapItemId') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Judul Plan <span class="text-danger">*</span></label>
                            <input type="text" class="input border-primary" wire:model.live="editPlanTitle" placeholder="Judul rencana..." />
                            @error('editPlanTitle') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Deskripsi Plan</label>
                            <textarea class="input min-h-[110px]" wire:model.live="editPlanText" placeholder="Deskripsi rencana..."></textarea>
                            @error('editPlanText') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Kenapa terkait Big Rock & Roadmap <span class="text-danger">*</span></label>
                            <textarea class="input min-h-[90px]" wire:model.live="editPlanRelationReason" placeholder="Jelaskan keterkaitannya..."></textarea>
                            @error('editPlanRelationReason') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Realisasi --}}
                    <div class="bg-surface border border-border rounded-xl p-4 space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-text">Realisasi</p>
                            <p class="text-sm text-muted mt-0.5">Realisasi bisa berbeda dari plan (misal ada halangan meeting mendadak).</p>
                        </div>

                        <div>
                            <label class="label">Status Realisasi</label>
                            <select class="input border-primary" wire:model.live="editRealizationStatus">
                                <option value="draft">Missing</option>
                                <option value="done">Done</option>
                                <option value="partial">Partial</option>
                                <option value="not_done">Not Done</option>
                                <option value="blocked">Blocked</option>
                            </select>
                            @error('editRealizationStatus') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Deskripsi Realisasi</label>
                            <textarea class="input min-h-[110px]" wire:model.live="editRealizationText" placeholder="Apa yang benar-benar dikerjakan..."></textarea>
                            @error('editRealizationText') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Alasan (jika tidak Done)</label>
                            <textarea class="input min-h-[90px]" wire:model.live="editRealizationReason" placeholder="Alasan jika belum sesuai rencana..."></textarea>
                            @error('editRealizationReason') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Attachment --}}
                    <div class="bg-surface border border-border rounded-xl p-4 space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-text">Lampiran</p>
                            <p class="text-sm text-muted mt-0.5">Anda bisa menghapus lampiran lama dan menambah lampiran baru. Maksimal 50MB per file.</p>
                        </div>

                        @if(!empty($existingAttachments))
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-muted uppercase">Lampiran saat ini</p>
                                @foreach($existingAttachments as $att)
                                    <label class="flex items-center gap-2 text-sm text-text">
                                        <input
                                            type="checkbox"
                                            class="w-4 h-4 rounded accent-primary border-border"
                                            wire:model="removeAttachmentIds"
                                            value="{{ $att['id'] }}"
                                        />
                                        <span class="truncate">{{ $att['name'] }}</span>
                                        <span class="text-sm text-muted">(hapus)</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <label class="label">Tambah lampiran baru</label>
                            <input type="file" class="input" wire:model="newAttachments" multiple />
                            @error('newAttachments.*') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                            <p class="text-sm text-muted mt-2">Tips: kalau gagal upload, kemungkinan batas upload server masih kecil.</p>
                        </div>

                        <div wire:loading wire:target="newAttachments" class="text-sm text-muted">Mengunggah...</div>
                    </div>

                    <div>
                        <label class="label">Alasan Override <span class="text-danger">*</span></label>
                        <textarea class="input min-h-[120px]" placeholder="Jelaskan alasan override ini..." wire:model.live="overrideReason"></textarea>
                        @error('overrideReason') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="button" class="btn-secondary flex-1" @click="detailOpen = false; $wire.closeDrawer()">Batal</button>
                        <button
                            type="button"
                            class="btn-primary flex-1 flex items-center justify-center gap-2"
                            wire:click="saveOverride"
                            wire:loading.attr="disabled"
                            wire:target="saveOverride"
                        >
                            <span wire:loading.remove wire:target="saveOverride">Simpan Override</span>
                            <span wire:loading wire:target="saveOverride" class="flex items-center gap-2">
                                <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Audit section --}}
                <div class="mt-8">
                    <p class="text-sm font-semibold text-text">Audit</p>
                    @if(empty($lastAudit))
                        <p class="text-sm text-muted mt-2">Belum ada override sebelumnya untuk entry ini.</p>
                    @else
                        <div class="mt-3 bg-app-bg border border-border rounded-xl p-4">
                            <p class="text-sm font-medium text-text">Override terakhir</p>
                            <p class="text-sm text-muted mt-1">{{ $lastAudit['actor'] ?? '-' }} · {{ $lastAudit['time'] ?? '-' }}</p>
                            <p class="text-sm text-text mt-3 whitespace-pre-wrap">{{ $lastAudit['reason'] ?? '' }}</p>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-3">
                            <div class="bg-surface border border-border rounded-xl p-4">
                                <p class="text-sm text-muted uppercase font-semibold">Before</p>
                                <pre class="mt-2 text-sm text-text bg-app-bg rounded-lg p-3 overflow-x-auto whitespace-pre-wrap">{{ json_encode(($lastAudit['changes']['before'] ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                            <div class="bg-surface border border-border rounded-xl p-4">
                                <p class="text-sm text-muted uppercase font-semibold">After</p>
                                <pre class="mt-2 text-sm text-text bg-app-bg rounded-lg p-3 overflow-x-auto whitespace-pre-wrap">{{ json_encode(($lastAudit['changes']['after'] ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
