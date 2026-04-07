{{-- Confirmation Modal Component
     Usage: <x-ui.confirmation-modal
               title="Hapus User"
               message="Yakin ingin menghapus user ini?"
               confirm-label="Ya, Hapus"
               confirm-action="deleteUser"
               danger
            />

     Control visibility with Alpine x-show on parent or via x-model="showConfirm"
--}}

@props([
    'title' => 'Konfirmasi Aksi',
    'message' => 'Apakah kamu yakin? Aksi ini tidak bisa dibatalkan.',
    'confirmLabel' => 'Ya, Lanjutkan',
    'confirmAction' => '',
    'cancelLabel' => 'Batal',
    'danger' => false,
])

<div
    x-show="showConfirm"
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
    <div class="absolute inset-0 bg-black/40" @click="showConfirm = false"></div>

    {{-- Modal --}}
    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-surface rounded-2xl p-6 w-full max-w-sm shadow-xl"
    >
        <h3 class="text-base font-semibold text-text">{{ $title }}</h3>
        <p class="text-sm text-muted mt-2">{{ $message }}</p>
        <div class="mt-6 flex gap-3">
            <button @click="showConfirm = false" class="btn-secondary flex-1">
                {{ $cancelLabel }}
            </button>
            {{-- TODO: Hubungkan $confirmAction ke wire:click --}}
            <button
                @if($confirmAction) wire:click="{{ $confirmAction }}" @endif
                @click="showConfirm = false"
                class="{{ $danger ? 'btn-danger' : 'btn-primary' }} flex-1"
            >
                {{ $confirmLabel }}
            </button>
        </div>
    </div>
</div>
