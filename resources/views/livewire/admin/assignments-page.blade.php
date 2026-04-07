{{--
    Admin Assignments Page
    Route: /admin/assignment
    Component: App\Livewire\Admin\AssignmentsPage
--}}

<x-layouts.app title="Assignment HoD">
    @php
        $assignments = \App\Models\HodAssignment::with(['hod', 'division'])->get();
    @endphp

    <x-ui.page-header title="Assignment HoD" description="Tentukan HoD yang bertanggung jawab atas setiap divisi">
        <x-slot:actions>
            {{-- TODO: wire:click="openAdd" --}}
            <button class="btn-primary gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Assignment
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Desktop Table --}}
    <div class="hidden md:block">
        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-app-bg border-b border-border">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Nama HoD</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Email</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Divisi yang Di-assign</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($assignments as $assign)
                        <tr class="hover:bg-app-bg transition-colors">
                            <td class="px-4 py-3.5 font-medium text-text">{{ $assign->hod?->name }}</td>
                            <td class="px-4 py-3.5 text-muted">{{ $assign->hod?->email }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex flex-wrap gap-1.5">
                                    @if($assign->division)
                                        <span class="badge-primary">{{ $assign->division->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- TODO: wire:click="openEdit({{ $assign->id }})" --}}
                                    <button class="text-sm text-primary font-medium hover:underline">Edit</button>
                                    {{-- TODO: wire:click="delete({{ $assign->id }})" + confirmation --}}
                                    <button class="text-sm text-danger font-medium hover:underline">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Cards --}}
    <div class="block md:hidden space-y-3">
        @forelse($assignments as $assign)
            <x-ui.card>
                <p class="font-semibold text-text">{{ $assign->hod?->name }}</p>
                <p class="text-sm text-muted mt-0.5">{{ $assign->hod?->email }}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @if($assign->division)
                        <span class="badge-primary">{{ $assign->division->name }}</span>
                    @endif
                </div>
                <div class="mt-3 pt-3 border-t border-border flex gap-2">
                    <button class="text-sm text-primary font-medium">Edit</button>
                    <button class="text-sm text-danger font-medium ml-auto">Hapus</button>
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty-state
                title="Belum ada assignment HoD."
                description="Assign HoD ke divisi terlebih dahulu."
                cta-label="Tambah Assignment"
            />
        @endforelse
    </div>
</x-layouts.app>
