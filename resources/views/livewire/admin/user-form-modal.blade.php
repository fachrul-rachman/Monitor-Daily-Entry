{{--
    Admin User Form Modal — Create/Edit
    Component: App\Livewire\Admin\UserFormModal
--}}

<div
    x-data="{ showModal: @entangle('show') }"
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
            <button @click="showModal = false" class="text-muted hover:text-text text-lg">&times;</button>
        </div>

        <form class="space-y-4" wire:submit.prevent="save">
            {{-- Name --}}
            <div>
                <label class="label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" class="input" placeholder="Nama lengkap user" wire:model.defer="name" />
                @error('name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="label">Email <span class="text-danger">*</span></label>
                <input type="email" class="input" placeholder="email@perusahaan.com" wire:model.defer="email" />
                @error('email') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Role --}}
            <div>
                <label class="label">Role <span class="text-danger">*</span></label>
                <select class="input" wire:model.live="role">
                    <option value="">Pilih role...</option>
                    @foreach($availableRoles as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Division --}}
            <div>
                <label class="label">Divisi</label>
                <select class="input" wire:model.defer="division">
                    <option value="">Pilih divisi...</option>
                    @foreach($availableDivisions as $div)
                        <option value="{{ $div }}">{{ $div }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-muted mt-1">Divisi yang tersedia bergantung pada role yang dipilih</p>
                @error('division') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Password --}}
            <div>
                <label class="label">
                    Password
                    @if(!$isEdit)
                        <span class="text-danger">*</span>
                    @endif
                </label>
                <input
                    type="password"
                    class="input"
                    placeholder="{{ $isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}"
                    wire:model.defer="password"
                />
                @if($isEdit)
                    <p class="text-xs text-muted mt-1">Kosongkan jika tidak ingin mengubah password</p>
                @endif
                @error('password') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                <input
                    type="checkbox"
                    id="is-active"
                    class="w-4 h-4 rounded border-border text-primary accent-primary"
                    wire:model="isActive"
                >
                <label for="is-active" class="text-sm text-text cursor-pointer">User aktif</label>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                <button type="submit" class="btn-primary flex-1 flex items-center justify-center gap-2" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        {{ $isEdit ? 'Simpan Perubahan' : 'Tambah User' }}
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
