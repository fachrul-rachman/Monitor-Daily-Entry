{{--
    HoD Big Rock Form Modal
    Component: App\Livewire\Hod\BigRockFormModal
--}}

@php
    $editingId = null;
    $isEdit = !is_null($editingId);
@endphp

<div
    x-data="{ showModal: false }"
    @open-big-rock-form.window="showModal = true"
    x-show="showModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>

    <div class="relative bg-surface rounded-2xl p-6 w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">
                {{ $isEdit ? 'Edit Big Rock' : 'Tambah Big Rock Baru' }}
            </h3>
            <button @click="showModal = false" class="text-muted hover:text-text text-lg">✕</button>
        </div>

        {{-- TODO: wire:submit.prevent="save" --}}
        <form class="space-y-4">
            <div>
                <label class="label">Judul <span class="text-danger">*</span></label>
                {{-- TODO: wire:model.defer="title" --}}
                <input type="text" class="input" placeholder="Judul big rock" />
            </div>

            <div>
                <label class="label">Deskripsi</label>
                {{-- TODO: wire:model.defer="description" --}}
                <textarea class="input min-h-[80px]" rows="3" placeholder="Deskripsi tujuan big rock..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Tanggal Mulai <span class="text-danger">*</span></label>
                    {{-- TODO: wire:model.defer="startDate" --}}
                    <input type="date" class="input" />
                </div>
                <div>
                    <label class="label">Tanggal Selesai <span class="text-danger">*</span></label>
                    {{-- TODO: wire:model.defer="endDate" --}}
                    <input type="date" class="input" />
                </div>
            </div>

            <div>
                <label class="label">Status</label>
                {{-- TODO: wire:model="status" --}}
                <select class="input">
                    <option value="active">Active</option>
                    <option value="on_track">On Track</option>
                    <option value="at_risk">At Risk</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                <button type="submit" class="btn-primary flex-1">
                    {{ $isEdit ? 'Simpan' : 'Tambah Big Rock' }}
                </button>
            </div>
        </form>
    </div>
</div>
