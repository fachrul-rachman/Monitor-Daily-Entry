<div>
    <x-ui.page-header title="Cuti & Izin" description="Persetujuan pengajuan cuti/izin untuk HoD (semua divisi)" />

    <x-ui.card>
        <h3 class="text-sm font-semibold text-text mb-3">Pengajuan HoD (Pending)</h3>

        <div class="mb-4">
            <label class="label">Catatan keputusan (opsional)</label>
            <input type="text" class="input" placeholder="Contoh: Disetujui, mohon atur delegasi." wire:model.live="decisionNote" />
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
</div>

