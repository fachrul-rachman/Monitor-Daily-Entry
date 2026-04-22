{{--
    Admin Report Settings Page (content only, layout via components.layouts.app)
--}}

<div>
    <x-ui.page-header
        title="Pengaturan Window Laporan"
        description="Atur jam buka dan tutup untuk input plan dan realisasi harian"
    />

    <div class="max-w-xl">
        {{-- Current settings info box --}}
        <div class="bg-primary-light border border-primary/20 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold text-primary">Pengaturan Aktif Saat Ini</span>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-sm text-muted">Plan Window</p>
                    <p class="text-text font-medium">{{ $currentSettings['plan_open'] }} - {{ $currentSettings['plan_close'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted">Realisasi Window</p>
                    <p class="text-text font-medium">{{ $currentSettings['realization_open'] }} - {{ $currentSettings['realization_close'] }}</p>
                </div>
            </div>
        </div>

        {{-- Warning box --}}
        @if($hasWarning)
            <div class="bg-warning-bg border border-warning/20 rounded-xl p-4 flex items-start gap-3 mb-6">
                <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <p class="text-sm text-warning">{{ $warningMessage }}</p>
            </div>
        @endif

        <form class="space-y-6" wire:submit.prevent="save">
            {{-- Plan Section --}}
            <div>
                <h3 class="text-base font-semibold text-text mb-3" style="font-family: 'DM Sans', sans-serif;">Plan</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Jam Buka Plan</label>
                        <input type="time" class="input" wire:model="planOpenTime" />
                        @error('planOpenTime') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Jam Tutup Plan</label>
                        <input type="time" class="input" wire:model="planCloseTime" />
                        @error('planCloseTime') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-sm text-muted mt-2">Plan hanya dapat diisi dalam rentang waktu ini setiap hari kerja.</p>
            </div>

            {{-- Realization Section --}}
            <div>
                <h3 class="text-base font-semibold text-text mb-3" style="font-family: 'DM Sans', sans-serif;">Realisasi</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Jam Buka Realisasi</label>
                        <input type="time" class="input" wire:model="realizationOpenTime" />
                        @error('realizationOpenTime') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Jam Tutup Realisasi</label>
                        <input type="time" class="input" wire:model="realizationCloseTime" />
                        @error('realizationCloseTime') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-sm text-muted mt-2">Realisasi diisi setelah jam kerja selesai.</p>
            </div>

            {{-- Discord Section --}}
            <div>
                <h3 class="text-base font-semibold text-text mb-3" style="font-family: 'DM Sans', sans-serif;">Notifikasi Discord</h3>

                <div class="bg-app-bg border border-border rounded-xl p-4 mb-4">
                    <p class="text-sm text-text font-medium">Executive summary untuk Director</p>
                    <p class="text-sm text-muted mt-1">Sistem mengirim ringkasan temuan hari itu ke Discord (hanya medium & high). Jika tidak ada temuan, sistem tidak mengirim.</p>
                </div>

                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer min-h-[44px] transition-colors {{ $discordEnabled ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text' }}">
                    <input type="checkbox" wire:model.live="discordEnabled" class="w-4 h-4 rounded accent-primary border-border">
                    <span class="text-sm font-medium">Aktifkan Discord summary</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="label">Jam Kirim (WIB)</label>
                        <input type="time" class="input @error('discordSummaryTime') input-error @enderror" wire:model="discordSummaryTime" />
                        @error('discordSummaryTime') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Webhook URL</label>
                        <input type="text" class="input @error('discordWebhookUrl') input-error @enderror" placeholder="https://discord.com/api/webhooks/..." wire:model.live="discordWebhookUrl" />
                        @error('discordWebhookUrl') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <p class="text-sm text-muted mt-2">Webhook ini cukup 1 untuk semua ringkasan harian.</p>
            </div>

            {{-- Submit --}}
            <div class="flex flex-col md:flex-row gap-2">
                <button
                    type="submit"
                    class="btn-primary w-full md:w-auto flex items-center justify-center gap-2"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">Simpan Pengaturan</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span>Menyimpan...</span>
                    </span>
                </button>

                <button
                    type="button"
                    class="btn-secondary w-full md:w-auto flex items-center justify-center gap-2"
                    wire:click="testDiscordToday"
                    wire:loading.attr="disabled"
                    wire:target="testDiscordToday"
                >
                    <span wire:loading.remove wire:target="testDiscordToday">Test Kirim Hari Ini</span>
                    <span wire:loading wire:target="testDiscordToday" class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 border-2 border-text/20 border-t-text rounded-full animate-spin"></span>
                        <span>Mengirim...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
