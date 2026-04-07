{{--
    Admin User Form Modal — Create/Edit
    Component: App\Livewire\Admin\UserFormModal
--}}

{{-- TODO: Bind semua properties dari Livewire --}}
@php
    $userId = null; // null = create, value = edit
    $isEdit = !is_null($userId);
    $availableRoles = ['Admin', 'Director', 'HoD', 'Manager'];
    $availableDivisions = ['Operasional', 'Keuangan', 'IT', 'Marketing'];
@endphp

{{-- TODO: Ganti x-show dengan wire:model atau $showAddModal --}}
<div
    x-data="{ showModal: true }"
    x-show="showModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    {{-- Overlay --}}
    <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>

    {{-- Modal --}}
    <div class="relative bg-surface rounded-2xl p-6 w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">
                {{ $isEdit ? 'Edit User' : 'Tambah User Baru' }}
            </h3>
            <button @click="showModal = false" class="text-muted hover:text-text text-lg">✕</button>
        </div>

        {{-- TODO: wire:submit.prevent="save" --}}
        <form class="space-y-4">
            {{-- Name --}}
            <div>
                <label class="label">Nama Lengkap <span class="text-danger">*</span></label>
                {{-- TODO: wire:model.defer="name" --}}
                <input type="text" class="input" placeholder="Nama lengkap user" />
                {{-- TODO: @error('name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror --}}
            </div>

            {{-- Email --}}
            <div>
                <label class="label">Email <span class="text-danger">*</span></label>
                {{-- TODO: wire:model.defer="email" --}}
                <input type="email" class="input" placeholder="email@perusahaan.com" />
            </div>

            {{-- Role --}}
            <div>
                <label class="label">Role <span class="text-danger">*</span></label>
                {{-- TODO: wire:model.live="role" (triggers updatedRole) --}}
                <select class="input">
                    <option value="">Pilih role...</option>
                    @foreach($availableRoles as $role)
                        <option value="{{ strtolower($role) }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Division (depends on role) --}}
            <div>
                <label class="label">Divisi <span class="text-danger">*</span></label>
                {{-- TODO: wire:model.defer="division" --}}
                <select class="input">
                    <option value="">Pilih divisi...</option>
                    @foreach($availableDivisions as $div)
                        <option value="{{ strtolower($div) }}">{{ $div }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-muted mt-1">Divisi yang tersedia bergantung pada role yang dipilih</p>
            </div>

            {{-- Password --}}
            <div>
                <label class="label">
                    Password
                    @if(!$isEdit)
                        <span class="text-danger">*</span>
                    @endif
                </label>
                {{-- TODO: wire:model.defer="password" --}}
                <input type="password" class="input" placeholder="{{ $isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}" />
                @if($isEdit)
                    <p class="text-xs text-muted mt-1">Kosongkan jika tidak ingin mengubah password</p>
                @endif
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                {{-- TODO: wire:model="isActive" --}}
                <input type="checkbox" id="is-active" checked class="w-4 h-4 rounded border-border text-primary accent-primary">
                <label for="is-active" class="text-sm text-text cursor-pointer">User aktif</label>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                {{-- TODO: wire:loading.attr="disabled" --}}
                <button type="submit" class="btn-primary flex-1">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Tambah User' }}
                </button>
            </div>
        </form>
    </div>
</div>
