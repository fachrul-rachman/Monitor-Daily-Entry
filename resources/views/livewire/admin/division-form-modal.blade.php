{{--
    Admin Division Form Modal — Create/Edit
    Component: App\Livewire\Admin\DivisionFormModal
--}}

<div
    x-data="{ showModal: @entangle('show') }"
    x-show="showModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>

    <div class="relative bg-surface rounded-2xl p-6 w-full max-w-sm shadow-xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">
                {{ $isEdit ? 'Edit Divisi' : 'Tambah Divisi Baru' }}
            </h3>
            <button @click="showModal = false" class="text-muted hover:text-text text-lg">&times;</button>
        </div>

        <form class="space-y-4" wire:submit.prevent="save">
            <div>
                <label class="label">Nama Divisi <span class="text-danger">*</span></label>
                <input type="text" class="input" placeholder="Nama divisi" wire:model.defer="name" />
                @error('name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <input
                    type="checkbox"
                    id="div-active"
                    class="w-4 h-4 rounded border-border text-primary accent-primary"
                    wire:model="isActive"
                >
                <label for="div-active" class="text-sm text-text cursor-pointer">Divisi aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                <button type="submit" class="btn-primary flex-1 flex items-center justify-center gap-2" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        {{ $isEdit ? 'Simpan' : 'Tambah Divisi' }}
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span>Menyimpan...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
