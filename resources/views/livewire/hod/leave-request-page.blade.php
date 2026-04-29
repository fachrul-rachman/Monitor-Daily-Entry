<div>
    <x-ui.page-header title="Cuti & Izin" description="Ajukan cuti/izin dan setujui pengajuan Manager di divisi Anda" />

    <div class="mb-4 flex gap-2 border-b border-border">
        <button
            type="button"
            class="px-4 py-3 text-sm min-h-[44px] transition-colors {{ $tab === 'approve' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted hover:text-text' }}"
            wire:click="switchTab('approve')"
        >Perlu Persetujuan</button>
        <button
            type="button"
            class="px-4 py-3 text-sm min-h-[44px] transition-colors {{ $tab === 'mine' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted hover:text-text' }}"
            wire:click="switchTab('mine')"
        >Pengajuan Saya</button>
    </div>

    @if($tab === 'approve')
        <x-ui.card>
            <h3 class="text-sm font-semibold text-text mb-3">Pengajuan Manager (Pending)</h3>

            <div class="mb-4">
                <label class="label">Catatan keputusan (opsional)</label>
                <input type="text" class="input" placeholder="Contoh: Disetujui, mohon update handover." wire:model.live="decisionNote" />
            </div>

            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-app-bg border-b border-border">
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">User</th>
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Divisi</th>
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Tanggal</th>
                            <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Tipe</th>
                            <th class="text-right px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($pending as $r)
                            <tr class="hover:bg-app-bg transition-colors">
                                <td class="px-4 py-3.5 text-text">{{ $r->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-text">{{ $r->division?->name ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-text">
                                    @php($s = optional($r->start_date)->toDateString())
                                    @php($e = optional($r->end_date)->toDateString())
                                    @if($s && $e && $s === $e)
                                        {{ \Illuminate\Support\Carbon::parse($s)->translatedFormat('j M Y') }}
                                    @elseif($s && $e)
                                        {{ \Illuminate\Support\Carbon::parse($s)->translatedFormat('j M Y') }} – {{ \Illuminate\Support\Carbon::parse($e)->translatedFormat('j M Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-text">{{ $r->type }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="btn-primary px-4" wire:click="approve({{ $r->id }})">Setujui</button>
                                        <button type="button" class="btn-danger px-4" wire:click="reject({{ $r->id }})">Tolak</button>
                                        <button type="button" class="btn-secondary px-4" wire:click="cancel({{ $r->id }})">Batalkan</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-muted">Tidak ada pengajuan pending.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $pending->links() }}
            </div>
        </x-ui.card>
    @endif

    @if($tab === 'mine')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-text mb-3">Ajukan Cuti / Izin (HoD)</h3>

                <form class="space-y-4" wire:submit.prevent="submit">
                    <div>
                        <label class="label">Divisi <span class="text-danger">*</span></label>
                        <select class="input" wire:model.live="divisionId">
                            @foreach($divisionOptions as $d)
                                <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                            @endforeach
                        </select>
                        @error('divisionId') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Tipe <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="input"
                            placeholder="Contoh: Cuti Tahunan / Izin Sakit / Izin Pribadi"
                            wire:model.live="type"
                        />
                        @error('type') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="input" wire:model.live="startDate" />
                            @error('startDate') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Sampai <span class="text-danger">*</span></label>
                            <input type="date" class="input" wire:model.live="endDate" />
                            @error('endDate') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">Alasan (opsional)</label>
                        <textarea
                            class="input min-h-[90px]"
                            placeholder="Tulis alasan singkat (opsional)"
                            wire:model.live="reason"
                        ></textarea>
                        @error('reason') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="submit">
                        <span wire:loading.remove wire:target="submit">Kirim Pengajuan</span>
                        <span wire:loading wire:target="submit">Mengirim…</span>
                    </button>
                </form>
            </x-ui.card>

            <x-ui.card>
                <h3 class="text-sm font-semibold text-text mb-3">Riwayat Pengajuan Saya</h3>

                <div class="overflow-x-auto rounded-xl border border-border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-app-bg border-b border-border">
                                <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Tanggal</th>
                                <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Tipe</th>
                                <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($mine as $r)
                                <tr class="hover:bg-app-bg transition-colors">
                                    <td class="px-4 py-3.5 text-text">
                                        @php($s = optional($r->start_date)->toDateString())
                                        @php($e = optional($r->end_date)->toDateString())
                                        @if($s && $e && $s === $e)
                                            {{ \Illuminate\Support\Carbon::parse($s)->translatedFormat('j M Y') }}
                                        @elseif($s && $e)
                                            {{ \Illuminate\Support\Carbon::parse($s)->translatedFormat('j M Y') }} – {{ \Illuminate\Support\Carbon::parse($e)->translatedFormat('j M Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-text">{{ $r->type }}</td>
                                    <td class="px-4 py-3.5">
                                        <x-ui.status-badge :status="$r->status" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-muted">Belum ada pengajuan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $mine->links() }}
                </div>
            </x-ui.card>
        </div>
    @endif
</div>

