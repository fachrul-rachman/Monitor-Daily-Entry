{{-- Admin Users Page (content only, layout via components.layouts.app) --}}

<div>
    {{-- Page Header --}}
    <x-ui.page-header title="Users" description="Kelola akun pengguna sistem">
        <x-slot:actions>
            <button class="btn-primary gap-2" wire:click="openAddModal">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah User
            </button>
            <button class="btn-secondary gap-2" wire:click="openImportModal">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Bulk Upload
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filter Bar --}}
    <div class="mb-6" x-data="{ filterOpen: false }">
        <div class="hidden md:flex gap-3 flex-wrap items-center">
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input
                    type="text"
                    placeholder="Cari user..."
                    class="input pl-9"
                    wire:model.live.debounce.300ms="search"
                >
            </div>
            <select class="input w-40" wire:model.live="filterRole">
                <option value="">Semua Role</option>
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select class="input w-44" wire:model.live="filterDivision">
                <option value="">Semua Divisi</option>
                @foreach($divisions as $div)
                    <option value="{{ $div }}">{{ $div }}</option>
                @endforeach
            </select>
            <select class="input w-36" wire:model.live="filterStatus">
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Non Aktif</option>
                <option value="archived">Diarsipkan</option>
            </select>
            <button type="button" class="text-sm text-muted hover:text-text transition-colors" wire:click="resetFilters">
                Reset
            </button>
        </div>

        <div class="flex gap-2 items-center md:hidden">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input
                    type="text"
                    placeholder="Cari..."
                    class="input pl-9"
                    wire:model.live.debounce.300ms="search"
                >
            </div>
            <button @click="filterOpen = true" class="btn-secondary gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
        </div>

        {{-- Mobile filter sheet --}}
        <div x-show="filterOpen" class="fixed inset-0 z-40 md:hidden" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="filterOpen = false"></div>
            <div
                class="absolute bottom-0 left-0 right-0 bg-surface rounded-t-2xl p-5 space-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
            >
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-text">Filter</p>
                    <button @click="filterOpen = false" class="text-muted text-lg">&times;</button>
                </div>
                <div>
                    <label class="label">Role</label>
                    <select class="input" wire:model.live="filterRole">
                        <option value="">Semua Role</option>
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Divisi</label>
                    <select class="input" wire:model.live="filterDivision">
                        <option value="">Semua Divisi</option>
                        @foreach($divisions as $div)
                            <option value="{{ $div }}">{{ $div }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select class="input" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Non Aktif</option>
                        <option value="archived">Diarsipkan</option>
                    </select>
                </div>
                <button @click="filterOpen = false" class="btn-primary w-full">Terapkan</button>
            </div>
        </div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden md:block">
        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-app-bg border-b border-border">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Nama</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Email</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Role</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Divisi</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Dibuat</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($users as $user)
                        <tr class="hover:bg-app-bg transition-colors">
                            <td class="px-4 py-3.5 font-medium text-text">{{ $user->name }}</td>
                            <td class="px-4 py-3.5 text-muted">{{ $user->email }}</td>
                            <td class="px-4 py-3.5 text-text">{{ $roles[$user->role] ?? ucfirst($user->role) }}</td>
                            <td class="px-4 py-3.5 text-text">{{ $user->division?->name ?? '-' }}</td>
                            <td class="px-4 py-3.5">
                                <x-ui.status-badge :status="$user->status ?? 'active'" />
                            </td>
                            <td class="px-4 py-3.5 text-muted">{{ optional($user->created_at)->format('d M Y') }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <button
                                    class="text-sm text-primary font-medium hover:underline"
                                    wire:click="openAddModal({{ $user->id }})"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <x-ui.pagination :paginator="$users" />
        </div>
    </div>

    {{-- Mobile Card List --}}
    <div class="block md:hidden space-y-3">
        @foreach($users as $user)
            <x-ui.card>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-text">{{ $user->name }}</p>
                        <p class="text-sm text-muted mt-0.5">{{ $user->email }}</p>
                    </div>
                    <x-ui.status-badge :status="$user->status ?? 'active'" />
                </div>
                <div class="mt-2 flex items-center gap-2 text-xs text-muted">
                    <span>{{ $roles[$user->role] ?? ucfirst($user->role) }}</span>
                    <span>&middot;</span>
                    <span>{{ $user->division?->name ?? '-' }}</span>
                </div>
            </x-ui.card>
        @endforeach

        <div class="mt-4">
            <x-ui.pagination :paginator="$users" />
        </div>
    </div>

    {{-- Modals --}}
    <livewire:admin.user-form-modal />
    <livewire:admin.user-import-modal />
</div>
