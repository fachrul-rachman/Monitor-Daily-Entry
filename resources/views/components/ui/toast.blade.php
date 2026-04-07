{{-- Toast Notification Component
     Placed in layout. Listens for Livewire dispatch:
     $this->dispatch('toast', message: 'Berhasil!', type: 'success');
--}}

<div
    x-data="{ show: false, message: '', type: 'success' }"
    x-on:toast.window="show = true; message = $event.detail.message; type = $event.detail.type || 'success'; setTimeout(() => show = false, 3000)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-6 right-4 z-50 max-w-sm"
    style="display: none;"
>
    <div
        :class="type === 'success' ? 'bg-success' : 'bg-danger'"
        class="text-white px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2"
    >
        {{-- Icon --}}
        <template x-if="type === 'success'">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </template>
        <template x-if="type !== 'success'">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </template>
        <span x-text="message"></span>
    </div>
</div>
