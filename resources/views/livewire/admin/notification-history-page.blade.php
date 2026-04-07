{{--
    Admin Notification History Page
    Route: /admin/notification-history
    Component: App\Livewire\Admin\NotificationHistoryPage
--}}

<x-layouts.app title="Riwayat Notifikasi">
    @php
        $notifications = [
            ['id' => 1, 'sent_at' => '7 Jul 2025 07:15', 'channel' => 'Email', 'status' => 'sent', 'summary' => '3 exception ditemukan di Operasional', 'severity' => 'major', 'payload' => 'Exception: Missing plan, Late realization, Blocked task. Recipients: siti@perusahaan.com, hendro@perusahaan.com', 'error' => null],
            ['id' => 2, 'sent_at' => '7 Jul 2025 06:30', 'channel' => 'WhatsApp', 'status' => 'failed', 'summary' => 'Daily reminder — Plan submission', 'severity' => 'minor', 'payload' => 'Reminder broadcast ke 48 user aktif', 'error' => 'API rate limit exceeded — retry after 60s'],
            ['id' => 3, 'sent_at' => '6 Jul 2025 17:00', 'channel' => 'Email', 'status' => 'sent', 'summary' => 'Realization window closing reminder', 'severity' => 'minor', 'payload' => 'Reminder: Window realisasi tutup jam 23:59. 12 user belum mengisi.', 'error' => null],
            ['id' => 4, 'sent_at' => '6 Jul 2025 08:00', 'channel' => 'Email', 'status' => 'sent', 'summary' => '5 exception ditemukan — Daily summary', 'severity' => 'medium', 'payload' => 'Exception summary harian untuk Director. 2 Major, 3 Minor across 3 divisions.', 'error' => null],
            ['id' => 5, 'sent_at' => '5 Jul 2025 07:00', 'channel' => 'Email', 'status' => 'failed', 'summary' => 'Plan reminder broadcast', 'severity' => 'minor', 'payload' => 'Daily plan reminder ke semua manager', 'error' => 'SMTP connection timeout — server unresponsive'],
        ];
    @endphp

    <x-ui.page-header title="Riwayat Notifikasi" description="Monitor semua notifikasi yang dikirim sistem" />

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <input type="date" class="input w-40" />
        <span class="text-muted hidden md:inline self-center">—</span>
        <input type="date" class="input w-40" />
        <select class="input w-36">
            <option value="">Semua Status</option>
            <option value="sent">Terkirim</option>
            <option value="failed">Gagal</option>
        </select>
        <select class="input w-36">
            <option value="">Semua Severity</option>
            <option value="major">Major</option>
            <option value="medium">Medium</option>
            <option value="minor">Minor</option>
        </select>
        <select class="input w-40">
            <option value="">Semua Divisi</option>
            <option>Operasional</option>
            <option>Keuangan</option>
            <option>IT</option>
        </select>
    </div>

    {{-- Desktop Table + expandable detail --}}
    <div class="hidden md:block" x-data="{ expandedId: null }">
        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-app-bg border-b border-border">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Waktu Kirim</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Channel</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Severity</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Ringkasan</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-muted uppercase tracking-wide">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($notifications as $notif)
                        <tr class="hover:bg-app-bg transition-colors">
                            <td class="px-4 py-3.5 text-muted whitespace-nowrap">{{ $notif['sent_at'] }}</td>
                            <td class="px-4 py-3.5 text-text">{{ $notif['channel'] }}</td>
                            <td class="px-4 py-3.5"><x-ui.status-badge :status="$notif['status']" /></td>
                            <td class="px-4 py-3.5"><x-ui.severity-badge :severity="$notif['severity']" /></td>
                            <td class="px-4 py-3.5 text-text max-w-[250px] truncate">{{ $notif['summary'] }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <button
                                    @click="expandedId = expandedId === {{ $notif['id'] }} ? null : {{ $notif['id'] }}"
                                    class="text-sm text-primary font-medium hover:underline"
                                >
                                    <span x-text="expandedId === {{ $notif['id'] }} ? 'Tutup' : 'Detail'">Detail</span>
                                </button>
                            </td>
                        </tr>
                        {{-- Expanded detail row --}}
                        <tr x-show="expandedId === {{ $notif['id'] }}" x-transition style="display:none;">
                            <td colspan="6" class="px-4 py-4 bg-app-bg">
                                <div class="max-w-2xl space-y-2">
                                    <div>
                                        <p class="text-xs font-semibold text-muted uppercase">Payload</p>
                                        <p class="text-sm text-text mt-1">{{ $notif['payload'] }}</p>
                                    </div>
                                    @if($notif['error'])
                                        <div class="bg-danger-bg rounded-lg p-3 mt-2">
                                            <p class="text-xs font-semibold text-danger">Error Message</p>
                                            <p class="text-sm text-danger mt-1">{{ $notif['error'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile expandable cards --}}
    <div class="block md:hidden space-y-3" x-data="{ expandedId: null }">
        @forelse($notifications as $notif)
            <x-ui.card class="cursor-pointer" @click="expandedId = expandedId === {{ $notif['id'] }} ? null : {{ $notif['id'] }}">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-text">{{ $notif['summary'] }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ $notif['sent_at'] }} · {{ $notif['channel'] }}</p>
                    </div>
                    <x-ui.status-badge :status="$notif['status']" />
                </div>
                <div class="mt-2">
                    <x-ui.severity-badge :severity="$notif['severity']" />
                </div>

                {{-- Expanded detail --}}
                <div x-show="expandedId === {{ $notif['id'] }}" x-transition class="mt-3 pt-3 border-t border-border" style="display:none;">
                    <p class="text-xs font-semibold text-muted mb-1">Payload</p>
                    <p class="text-sm text-text">{{ $notif['payload'] }}</p>
                    @if($notif['error'])
                        <div class="bg-danger-bg rounded-lg p-3 mt-2">
                            <p class="text-xs font-semibold text-danger">Error</p>
                            <p class="text-sm text-danger mt-0.5">{{ $notif['error'] }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty-state title="Tidak ada riwayat notifikasi" icon="bell" />
        @endforelse
    </div>

    {{-- Pagination dummy --}}
    <div class="mt-6 flex items-center justify-between text-sm text-muted">
        <span>Menampilkan 1-5 dari 24 notifikasi</span>
        <div class="flex gap-1">
            <button class="px-3 py-1.5 rounded-lg border border-border bg-surface text-muted cursor-not-allowed opacity-50">←</button>
            <button class="px-3 py-1.5 rounded-lg bg-primary text-white text-sm font-medium">1</button>
            <button class="px-3 py-1.5 rounded-lg border border-border bg-surface text-text hover:bg-app-bg">2</button>
            <button class="px-3 py-1.5 rounded-lg border border-border bg-surface text-text hover:bg-app-bg">→</button>
        </div>
    </div>
</x-layouts.app>
