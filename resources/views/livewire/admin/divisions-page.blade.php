{{--
    Admin Divisions Page (content only, layout via components.layouts.app)
--}}

<div>
    {{-- Page Header --}}
    <x-ui.page-header title="Divisi" description="Kelola divisi organisasi">
        <x-slot:actions>
            <button class="btn-primary gap-2" wire:click="openCreate">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Divisi
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Search --}}
    <div class="mb-6 max-w-xs relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input
            type="text"
            placeholder="Cari divisi..."
            class="input pl-9"
            wire:model.live.debounce.300ms="search"
        >
    </div>

    {{-- Desktop Table --}}
    <div class="hidden md:block">
        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-app-bg border-b border-border">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Nama Divisi</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Jumlah User</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Dibuat</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($divisions as $div)
                        <tr class="hover:bg-app-bg transition-colors">
                            <td class="px-4 py-3.5 font-medium text-text">{{ $div->name }}</td>
                            <td class="px-4 py-3.5">
                                <x-ui.status-badge :status="$div->status ?? 'active'" />
                            </td>
                            <td class="px-4 py-3.5 text-text">{{ $div->users_count }} user</td>
                            <td class="px-4 py-3.5 text-muted">{{ optional($div->created_at)->format('d M Y') }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button class="text-sm text-primary font-medium hover:underline" wire:click="openEdit({{ $div->id }})">Edit</button>
                                    <button class="text-sm text-warning font-medium hover:underline" wire:click="archive({{ $div->id }})">Arsipkan</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="hidden md:block mt-4">
        <x-ui.pagination :paginator="$divisions" />
    </div>

    {{-- Mobile Cards --}}
    <div class="block md:hidden space-y-3">
        @forelse($divisions as $div)
            <x-ui.card>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-text">{{ $div->name }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ $div->users_count }} user · {{ optional($div->created_at)->format('d M Y') }}</p>
                    </div>
                    <x-ui.status-badge :status="$div->status ?? 'active'" />
                </div>
                <div class="mt-3 pt-3 border-t border-border flex gap-2">
                    <button class="text-sm text-primary font-medium" wire:click="openEdit({{ $div->id }})">Edit</button>
                    <button class="text-sm text-warning font-medium ml-auto" wire:click="archive({{ $div->id }})">Arsipkan</button>
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty-state
                title="Belum ada divisi terdaftar."
                description="Tambah divisi pertama untuk memulai."
                cta-label="Tambah Divisi"
            />
        @endforelse

        <div class="mt-4">
            <x-ui.pagination :paginator="$divisions" />
        </div>
    </div>

    {{-- Modal --}}
    <livewire:admin.division-form-modal />
</div>
