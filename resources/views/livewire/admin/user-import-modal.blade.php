{{--
    Admin User Import Modal — Bulk Upload
    Component: App\Livewire\Admin\UserImportModal
--}}

<div
    x-data="{
        showModal: @entangle('show'),
        showPreview: @entangle('showPreview'),
        fileSelected: false
    }"
    x-show="showModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>

    <div class="relative bg-surface rounded-2xl p-6 w-full max-w-2xl shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">
                Bulk Upload User
            </h3>
            <button @click="showModal = false" class="text-muted hover:text-text text-lg">&times;</button>
        </div>

        {{-- Step 1: Upload --}}
        <div x-show="!showPreview">
            {{-- Download template --}}
            <div class="mb-4">
                <button
                    type="button"
                    wire:click="downloadTemplate"
                    class="text-sm text-primary font-medium hover:underline inline-flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Template Excel
                </button>
            </div>

            {{-- Upload area --}}
            <div
                class="border-2 border-dashed border-border rounded-xl p-8 text-center hover:border-primary/40 transition-colors"
                @dragover.prevent
                @drop.prevent
            >
                <svg class="w-10 h-10 text-muted mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <p class="text-sm text-text font-medium">Drag & drop file di sini</p>
                <p class="text-xs text-muted mt-1">atau</p>
                <label class="mt-2 btn-secondary inline-flex cursor-pointer">
                    Pilih File
                    <input
                        type="file"
                        class="hidden"
                        accept=".xlsx,.xls,.csv"
                        wire:model="file"
                        @change="fileSelected = true"
                    >
                </label>
                <p class="text-xs text-muted mt-2">Format: .xlsx, .xls, .csv</p>
                @if($file)
                    <p class="text-xs text-success mt-2">
                        File dipilih: <span class="font-medium">{{ $file->getClientOriginalName() }}</span>
                    </p>
                @else
                    <p class="text-xs text-muted mt-2">Belum ada file yang dipilih.</p>
                @endif
                @error('file') <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
            </div>

            <div class="mt-4 flex gap-3">
                <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                <button
                    type="button"
                    class="btn-primary flex-1 flex items-center justify-center gap-2"
                    :class="{ 'btn-disabled': !fileSelected }"
                    :disabled="!fileSelected"
                    wire:click="previewImport"
                    wire:target="previewImport"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="previewImport">Preview</span>
                    <span wire:loading wire:target="previewImport" class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span>Memproses...</span>
                    </span>
                </button>
            </div>
        </div>

        {{-- Step 2: Preview --}}
        <div x-show="showPreview" x-cloak>
            {{-- Summary --}}
            <div class="flex gap-3 mb-4">
                <div class="flex-1 bg-app-bg rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-text">{{ $totalRows }}</p>
                    <p class="text-xs text-muted">Total Baris</p>
                </div>
                <div class="flex-1 bg-success-bg rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-success">{{ $validRows }}</p>
                    <p class="text-xs text-muted">Valid</p>
                </div>
                <div class="flex-1 bg-danger-bg rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-danger">{{ $invalidRows }}</p>
                    <p class="text-xs text-muted">Invalid</p>
                </div>
            </div>

            {{-- Preview table --}}
            <div class="overflow-x-auto rounded-lg border border-border mb-4 max-h-60 overflow-y-auto">
                <table class="w-full text-xs">
                    <thead class="bg-app-bg sticky top-0">
                        <tr>
                            <th class="text-left px-3 py-2 font-semibold text-muted">Row</th>
                            <th class="text-left px-3 py-2 font-semibold text-muted">Nama</th>
                            <th class="text-left px-3 py-2 font-semibold text-muted">Email</th>
                            <th class="text-left px-3 py-2 font-semibold text-muted">Role</th>
                            <th class="text-left px-3 py-2 font-semibold text-muted">Divisi</th>
                            <th class="text-left px-3 py-2 font-semibold text-muted">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($previewData as $row)
                            <tr class="{{ $row['valid'] ? '' : 'bg-danger-bg/50' }}">
                                <td class="px-3 py-2 text-muted">{{ $row['row'] }}</td>
                                <td class="px-3 py-2 text-text">{{ $row['name'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-text">{{ $row['email'] }}</td>
                                <td class="px-3 py-2 text-text">{{ $row['role'] }}</td>
                                <td class="px-3 py-2 text-text">{{ $row['division'] ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    @if($row['valid'])
                                        <span class="badge-success">Valid</span>
                                    @else
                                        <span class="badge-danger">Invalid</span>
                                    @endif
                                </td>
                            </tr>
                            @if(!$row['valid'] && $row['reason'])
                                <tr class="bg-danger-bg/30">
                                    <td></td>
                                    <td colspan="5" class="px-3 py-1.5 text-xs text-danger">⚠ {{ $row['reason'] }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="showPreview = false" class="btn-secondary flex-1">← Kembali</button>
                <button
                    type="button"
                    class="btn-primary flex-1 flex items-center justify-center gap-2"
                    wire:click="applyImport"
                    wire:target="applyImport"
                    wire:loading.attr="disabled"
                    @disabled($validRows === 0)
                >
                    <span wire:loading.remove wire:target="applyImport">
                        Terapkan {{ $validRows }} Row Valid
                    </span>
                    <span wire:loading wire:target="applyImport" class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span>Mengimpor...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
