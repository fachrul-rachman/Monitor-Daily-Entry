{{--
    Admin Assignments Page
    Route: /admin/assignments
    Component: App\Livewire\Admin\AssignmentsPage
--}}

<div>
    <x-ui.page-header title="Assignment HoD" description="Tentukan HoD yang bertanggung jawab atas setiap divisi">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn-secondary"
                    wire:click="resetFilters"
                    wire:loading.attr="disabled"
                >
                    Reset
                </button>
                <button
                    type="button"
                    class="btn-primary gap-2"
                    wire:click="openAddModal"
                    wire:loading.attr="disabled"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Tambah Assignment
                </button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filters --}}
    <x-ui.card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="label">Search</label>
                <input type="text" class="input" placeholder="Cari nama HoD, email, atau divisi..." wire:model.live="search" />
            </div>
            <div>
                <label class="label">Divisi</label>
                <select class="input" wire:model.live="filterDivision">
                    <option value="">Semua divisi</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division }}">{{ $division }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <p class="text-sm text-muted">
                    Tip: satu divisi hanya bisa punya 1 HoD.
                </p>
            </div>
        </div>
    </x-ui.card>

    {{-- Desktop Table --}}
    <div class="hidden md:block">
        <div class="overflow-x-auto rounded-xl border border-border bg-surface">
            <table class="w-full text-sm">
                <thead>
                <tr class="bg-app-bg border-b border-border">
                    <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">HoD</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Email</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Divisi</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold text-muted uppercase tracking-wide">Aksi</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-border">
                @forelse($assignments as $assign)
                    <tr class="hover:bg-app-bg transition-colors">
                        <td class="px-4 py-3.5 font-medium text-text">{{ $assign->hod?->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 text-muted">{{ $assign->hod?->email ?? '—' }}</td>
                        <td class="px-4 py-3.5">
                            @if($assign->division)
                                <span class="badge-primary">{{ $assign->division->name }}</span>
                            @else
                                <span class="badge-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    class="text-sm text-primary font-medium hover:underline"
                                    wire:click="openAddModal({{ $assign->id }})"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="text-sm text-danger font-medium hover:underline"
                                    wire:click="deleteAssignment({{ $assign->id }})"
                                    wire:loading.attr="disabled"
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10">
                            <x-ui.empty-state
                                title="Belum ada assignment HoD."
                                description="Assign HoD ke divisi terlebih dahulu."
                                cta-label="Tambah Assignment"
                                wire:click="openAddModal"
                            />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <x-ui.pagination :paginator="$assignments" />
        </div>
    </div>

    {{-- Mobile Cards --}}
    <div class="block md:hidden space-y-3">
        @forelse($assignments as $assign)
            <x-ui.card>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-text truncate">{{ $assign->hod?->name ?? '—' }}</p>
                        <p class="text-sm text-muted mt-0.5 truncate">{{ $assign->hod?->email ?? '—' }}</p>
                    </div>
                    @if($assign->division)
                        <span class="badge-primary shrink-0">{{ $assign->division->name }}</span>
                    @endif
                </div>
                <div class="mt-3 pt-3 border-t border-border flex gap-2">
                    <button type="button" class="btn-secondary flex-1" wire:click="openAddModal({{ $assign->id }})">Edit</button>
                    <button type="button" class="btn-danger flex-1" wire:click="deleteAssignment({{ $assign->id }})" wire:loading.attr="disabled">Hapus</button>
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty-state
                title="Belum ada assignment HoD."
                description="Assign HoD ke divisi terlebih dahulu."
                cta-label="Tambah Assignment"
                wire:click="openAddModal"
            />
        @endforelse

        <div>
            <x-ui.pagination :paginator="$assignments" />
        </div>
    </div>

    <livewire:admin.assignment-form-modal />
</div>
