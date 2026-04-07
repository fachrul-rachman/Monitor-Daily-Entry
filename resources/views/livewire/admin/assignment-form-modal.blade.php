{{--
    Admin Assignment Form Modal
    Component: App\Livewire\Admin\AssignmentFormModal
    - HoD select + multi-select divisi via checkbox list
--}}

@php
    $hodUsers = [
        ['id' => 1, 'name' => 'Siti Rahayu', 'email' => 'siti@perusahaan.com'],
        ['id' => 2, 'name' => 'Hendro Wijaya', 'email' => 'hendro@perusahaan.com'],
        ['id' => 3, 'name' => 'Linda Permata', 'email' => 'linda@perusahaan.com'],
    ];
    $availableDivisions = [
        ['id' => 1, 'name' => 'Operasional'],
        ['id' => 2, 'name' => 'Keuangan'],
        ['id' => 3, 'name' => 'IT'],
        ['id' => 4, 'name' => 'Marketing'],
        ['id' => 5, 'name' => 'HR'],
        ['id' => 6, 'name' => 'R&D'],
    ];
@endphp

<div
    x-data="{ showModal: true, searchDiv: '' }"
    x-show="showModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>

    <div class="relative bg-surface rounded-2xl p-6 w-full max-w-md shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">Tambah Assignment</h3>
            <button @click="showModal = false" class="text-muted hover:text-text text-lg">✕</button>
        </div>

        {{-- TODO: wire:submit.prevent="save" --}}
        <form class="space-y-4">
            {{-- HoD select --}}
            <div>
                <label class="label">Pilih HoD <span class="text-danger">*</span></label>
                {{-- TODO: wire:model="selectedHod" --}}
                <select class="input">
                    <option value="">Pilih HoD...</option>
                    @foreach($hodUsers as $hod)
                        <option value="{{ $hod['id'] }}">{{ $hod['name'] }} ({{ $hod['email'] }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Multi-select divisi --}}
            <div>
                <label class="label">Pilih Divisi <span class="text-danger">*</span></label>
                {{-- Search within divisions --}}
                <input
                    type="text"
                    x-model="searchDiv"
                    placeholder="Cari divisi..."
                    class="input mb-2"
                />
                <div class="border border-border rounded-lg max-h-48 overflow-y-auto p-2 space-y-1">
                    @foreach($availableDivisions as $div)
                        <label
                            class="flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-app-bg cursor-pointer"
                            x-show="'{{ strtolower($div['name']) }}'.includes(searchDiv.toLowerCase()) || searchDiv === ''"
                        >
                            {{-- TODO: wire:model="selectedDivisions" --}}
                            <input type="checkbox" value="{{ $div['id'] }}" class="w-4 h-4 rounded border-border text-primary accent-primary">
                            <span class="text-sm text-text">{{ $div['name'] }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-muted mt-1">Pilih satu atau lebih divisi</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                <button type="submit" class="btn-primary flex-1">Simpan</button>
            </div>
        </form>
    </div>
</div>
