{{--
    Admin Division Form Modal — Create/Edit
    Component: App\Livewire\Admin\DivisionFormModal
--}}

@php
    $editingId = null;
    $isEdit = !is_null($editingId);
@endphp

<div
    x-data="{ showModal: true }"
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
            <button @click="showModal = false" class="text-muted hover:text-text text-lg">✕</button>
        </div>

        {{-- TODO: wire:submit.prevent="save" --}}
        <form class="space-y-4">
            <div>
                <label class="label">Nama Divisi <span class="text-danger">*</span></label>
                {{-- TODO: wire:model.defer="name" --}}
                <input type="text" class="input" placeholder="Nama divisi" />
            </div>

            <div class="flex items-center gap-3">
                {{-- TODO: wire:model="isActive" --}}
                <input type="checkbox" id="div-active" checked class="w-4 h-4 rounded border-border text-primary accent-primary">
                <label for="div-active" class="text-sm text-text cursor-pointer">Divisi aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                <button type="submit" class="btn-primary flex-1">
                    {{ $isEdit ? 'Simpan' : 'Tambah Divisi' }}
                </button>
            </div>
        </form>
    </div>
</div>
