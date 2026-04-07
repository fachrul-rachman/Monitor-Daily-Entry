{{--
    Admin Users Page
    Route: /admin/users
    Component: App\Livewire\Admin\UsersPage
--}}

<x-layouts.app title="Users">
    {{-- TODO: Bind semua data dari Livewire properties --}}
    @php
        $users = [
            ['id' => 1, 'name' => 'Budi Santoso', 'email' => 'budi@perusahaan.com', 'role' => 'Manager', 'division' => 'Operasional', 'status' => 'active', 'created' => '12 Jan 2025'],
            ['id' => 2, 'name' => 'Siti Rahayu', 'email' => 'siti@perusahaan.com', 'role' => 'HoD', 'division' => 'Keuangan', 'status' => 'active', 'created' => '15 Feb 2025'],
            ['id' => 3, 'name' => 'Ahmad Fauzi', 'email' => 'ahmad@perusahaan.com', 'role' => 'Manager', 'division' => 'IT', 'status' => 'inactive', 'created' => '20 Mar 2025'],
            ['id' => 4, 'name' => 'Dewi Lestari', 'email' => 'dewi@perusahaan.com', 'role' => 'Director', 'division' => '-', 'status' => 'active', 'created' => '5 Apr 2025'],
            ['id' => 5, 'name' => 'Rudi Hermawan', 'email' => 'rudi@perusahaan.com', 'role' => 'Manager', 'division' => 'Operasional', 'status' => 'archived', 'created' => '1 May 2025'],
        ];
        $roles = ['Admin', 'Director', 'HoD', 'Manager'];
        $divisions = ['Operasional', 'Keuangan', 'IT', 'Marketing'];
    @endphp

    {{-- Page Header --}}
    <x-ui.page-header title="Users" description="Kelola akun pengguna sistem">
        <x-slot:actions>
            {{-- TODO: wire:click="openAddModal" --}}
            <button class="btn-primary gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah User
            </button>
            {{-- TODO: wire:click="openImportModal" --}}
            <button class="btn-secondary gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Bulk Upload
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filter Bar --}}
    <div class="mb-6" x-data="{ filterOpen: false }">
        {{-- Desktop filters --}}
        <div class="hidden md:flex gap-3 flex-wrap items-center">
            {{-- TODO: wire:model.live.debounce.300ms="search" --}}
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" placeholder="Cari user..." class="input pl-9">
            </div>
            {{-- TODO: wire:model.live="filterRole" --}}
            <select class="input w-40">
                <option value="">Semua Role</option>
                @foreach($roles as $role)
                    <option>{{ $role }}</option>
                @endforeach
            </select>
            {{-- TODO: wire:model.live="filterDivision" --}}
            <select class="input w-44">
                <option value="">Semua Divisi</option>
                @foreach($divisions as $div)
                    <option>{{ $div }}</option>
                @endforeach
            </select>
            {{-- TODO: wire:model.live="filterStatus" --}}
            <select class="input w-36">
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Non Aktif</option>
                <option value="archived">Diarsipkan</option>
            </select>
            {{-- TODO: wire:click="resetFilters" --}}
            <button class="text-sm text-muted hover:text-text transition-colors">Reset</button>
        </div>

        {{-- Mobile filter --}}
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

        {{-- Mobile bottom sheet --}}
        <div x-show="filterOpen" class="fixed inset-0 z-40 md:hidden" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="filterOpen = false"></div>
            <div class="absolute bottom-0 left-0 right-0 bg-surface rounded-t-2xl p-5 space-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-text">Filter</p>
                    <button @click="filterOpen = false" class="text-muted text-lg">✕</button>
                </div>
                <div>
                    <label class="label">Role</label>
                    <select class="input">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                            <option>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Divisi</label>
                    <select class="input">
                        <option value="">Semua Divisi</option>
                        @foreach($divisions as $div)
                            <option>{{ $div }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select class="input">
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
                            <td class="px-4 py-3.5 font-medium text-text">{{ $user['name'] }}</td>
                            <td class="px-4 py-3.5 text-muted">{{ $user['email'] }}</td>
                            <td class="px-4 py-3.5 text-text">{{ $user['role'] }}</td>
                            <td class="px-4 py-3.5 text-text">{{ $user['division'] }}</td>
                            <td class="px-4 py-3.5"><x-ui.status-badge :status="$user['status']" /></td>
                            <td class="px-4 py-3.5 text-muted">{{ $user['created'] }}</td>
                            <td class="px-4 py-3.5 text-right" x-data="{ open: false }">
                                <div class="relative inline-block">
                                    <button @click="open = !open" class="p-1 rounded hover:bg-app-bg text-muted hover:text-text">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 w-44 bg-surface border border-border rounded-lg shadow-lg z-10 py-1" style="display:none;">
                                        <button class="w-full text-left px-4 py-2 text-sm text-text hover:bg-app-bg">Lihat Detail</button>
                                        {{-- TODO: wire:click="openEdit({{ $user['id'] }})" --}}
                                        <button class="w-full text-left px-4 py-2 text-sm text-text hover:bg-app-bg">Edit</button>
                                        {{-- TODO: wire:click="archiveUser({{ $user['id'] }})" --}}
                                        <button class="w-full text-left px-4 py-2 text-sm text-warning hover:bg-app-bg">Arsipkan</button>
                                        @if($user['status'] !== 'active')
                                            {{-- TODO: wire:click="deleteUser({{ $user['id'] }})" --}}
                                            <button class="w-full text-left px-4 py-2 text-sm text-danger hover:bg-app-bg">Hapus</button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Card List --}}
    <div class="block md:hidden space-y-3">
        @foreach($users as $user)
            <x-ui.card>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-text">{{ $user['name'] }}</p>
                        <p class="text-sm text-muted mt-0.5">{{ $user['email'] }}</p>
                    </div>
                    <x-ui.status-badge :status="$user['status']" />
                </div>
                <div class="mt-2 flex items-center gap-2 text-xs text-muted">
                    <span>{{ $user['role'] }}</span>
                    <span>·</span>
                    <span>{{ $user['division'] }}</span>
                </div>
                <div class="mt-3 pt-3 border-t border-border flex gap-2">
                    <button class="text-sm text-primary font-medium">Edit</button>
                    <button class="text-sm text-warning font-medium ml-auto">Arsipkan</button>
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Pagination (dummy) --}}
    <div class="mt-6 flex items-center justify-between text-sm text-muted">
        <span>Menampilkan 1-5 dari 48 user</span>
        <div class="flex gap-1">
            <button class="px-3 py-1.5 rounded-lg border border-border bg-surface text-muted cursor-not-allowed opacity-50">←</button>
            <button class="px-3 py-1.5 rounded-lg bg-primary text-white text-sm font-medium">1</button>
            <button class="px-3 py-1.5 rounded-lg border border-border bg-surface text-text hover:bg-app-bg">2</button>
            <button class="px-3 py-1.5 rounded-lg border border-border bg-surface text-text hover:bg-app-bg">3</button>
            <button class="px-3 py-1.5 rounded-lg border border-border bg-surface text-text hover:bg-app-bg">→</button>
        </div>
    </div>

    {{-- TODO: Include user form modal dan import modal --}}
    {{-- @livewire('admin.user-form-modal') --}}
    {{-- @livewire('admin.user-import-modal') --}}
</x-layouts.app>
