{{--
    Admin Assignment Form Modal
    Component: App\Livewire\Admin\AssignmentFormModal
    - Create: pilih HoD + beberapa divisi
    - Edit: 1 divisi saja (jelas & aman)
--}}

<div
    x-data="{ showModal: @entangle('show'), searchDiv: '' }"
    x-show="showModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/40" @click="$wire.close()"></div>

    <div class="relative bg-surface rounded-2xl p-6 w-full max-w-md shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">
                {{ $assignmentId ? 'Edit Assignment' : 'Tambah Assignment' }}
            </h3>
            <button type="button" @click="$wire.close()" class="text-muted hover:text-text text-lg">&times;</button>
        </div>

        <form class="space-y-4" wire:submit.prevent="save">
            <div>
                <label class="label">Pilih HoD <span class="text-danger">*</span></label>
                <select class="input" wire:model="selectedHodId">
                    <option value="">Pilih HoD...</option>
                    @foreach($hodUsers as $hod)
                        <option value="{{ $hod['id'] }}">{{ $hod['name'] }} ({{ $hod['email'] }})</option>
                    @endforeach
                </select>
                @error('selectedHodId') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Pilih Divisi <span class="text-danger">*</span></label>
                <input
                    type="text"
                    x-model="searchDiv"
                    placeholder="Cari divisi..."
                    class="input mb-2"
                />

                <div class="border border-border rounded-lg max-h-48 overflow-y-auto p-2 space-y-1">
                    @foreach($divisions as $div)
                        <label
                            class="flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-app-bg cursor-pointer"
                            x-show="'{{ strtolower($div['name']) }}'.includes(searchDiv.toLowerCase()) || searchDiv === ''"
                        >
                            <input
                                type="checkbox"
                                value="{{ $div['id'] }}"
                                class="w-4 h-4 rounded border-border text-primary accent-primary"
                                wire:model="selectedDivisionIds"
                                @disabled($assignmentId)
                            >
                            <span class="text-sm text-text">{{ $div['name'] }}</span>
                        </label>
                    @endforeach
                </div>

                @error('selectedDivisionIds') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                <p class="text-sm text-muted mt-1">
                    {{ $assignmentId ? 'Mode edit: divisi dikunci. Jika perlu pindah divisi, hapus & buat assignment baru.' : 'Pilih satu atau lebih divisi.' }}
                </p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="$wire.close()" class="btn-secondary flex-1" wire:loading.attr="disabled">Batal</button>
                <button
                    type="submit"
                    class="btn-primary flex-1 flex items-center justify-center gap-2"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">Simpan</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span>Menyimpan...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

