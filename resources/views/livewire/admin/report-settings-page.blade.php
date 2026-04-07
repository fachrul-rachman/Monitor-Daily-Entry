{{--
    Admin Report Settings Page
    Route: /admin/report-settings
    Component: App\Livewire\Admin\ReportSettingsPage
--}}

<x-layouts.app title="Pengaturan Window Laporan">
    @php
        // Dummy current settings
        $currentSettings = [
            'plan_open' => '08:00',
            'plan_close' => '17:00',
            'realization_open' => '15:00',
            'realization_close' => '23:59',
        ];
        $hasWarning = false;
        $warningMessage = 'Perhatian: Jam tutup lebih awal dari jam buka. Periksa kembali.';
    @endphp

    <x-ui.page-header title="Pengaturan Window Laporan" description="Atur jam buka dan tutup untuk input plan dan realisasi harian" />

    <div class="max-w-xl">
        {{-- Current settings info box --}}
        <div class="bg-primary-light border border-primary/20 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold text-primary">Pengaturan Aktif Saat Ini</span>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-xs text-muted">Plan Window</p>
                    <p class="text-text font-medium">{{ $currentSettings['plan_open'] }} – {{ $currentSettings['plan_close'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted">Realisasi Window</p>
                    <p class="text-text font-medium">{{ $currentSettings['realization_open'] }} – {{ $currentSettings['realization_close'] }}</p>
                </div>
            </div>
        </div>

        {{-- TODO: wire:submit.prevent="save" --}}
        <form class="space-y-6">
            {{-- Plan Section --}}
            <div>
                <h3 class="text-base font-semibold text-text mb-3" style="font-family: 'DM Sans', sans-serif;">Plan</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Jam Buka Plan</label>
                        {{-- TODO: wire:model="planOpenTime" --}}
                        <input type="time" class="input" value="{{ $currentSettings['plan_open'] }}" />
                    </div>
                    <div>
                        <label class="label">Jam Tutup Plan</label>
                        {{-- TODO: wire:model="planCloseTime" --}}
                        <input type="time" class="input" value="{{ $currentSettings['plan_close'] }}" />
                    </div>
                </div>
                <p class="text-xs text-muted mt-2">Plan hanya dapat diisi dalam rentang waktu ini setiap hari kerja</p>
            </div>

            {{-- Realization Section --}}
            <div>
                <h3 class="text-base font-semibold text-text mb-3" style="font-family: 'DM Sans', sans-serif;">Realisasi</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Jam Buka Realisasi</label>
                        {{-- TODO: wire:model="realizationOpenTime" --}}
                        <input type="time" class="input" value="{{ $currentSettings['realization_open'] }}" />
                    </div>
                    <div>
                        <label class="label">Jam Tutup Realisasi</label>
                        {{-- TODO: wire:model="realizationCloseTime" --}}
                        <input type="time" class="input" value="{{ $currentSettings['realization_close'] }}" />
                    </div>
                </div>
                <p class="text-xs text-muted mt-2">Realisasi diisi setelah jam kerja selesai</p>
            </div>

            {{-- Warning box --}}
            @if($hasWarning)
                <div class="bg-warning-bg border border-warning/20 rounded-xl p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p class="text-sm text-warning">{{ $warningMessage }}</p>
                </div>
            @endif

            {{-- Submit --}}
            {{-- TODO: wire:loading state --}}
            <button type="submit" class="btn-primary w-full md:w-auto">
                Simpan Pengaturan
            </button>
        </form>
    </div>
</x-layouts.app>
