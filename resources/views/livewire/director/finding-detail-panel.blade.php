{{--
    Director Finding Detail Panel
    Shared component for displaying finding hierarchy chain
    Usage: Include in drawer/panel context
--}}

@php
    // Dummy data — akan diganti oleh Livewire property
    $finding = [
        'title' => 'Missing plan 3 hari berturut-turut',
        'user' => 'Budi Santoso',
        'division' => 'Operasional',
        'severity' => 'major',
        'date' => '7 Jul 2025',
    ];
    $hierarchy = [
        'big_rock' => ['title' => 'Optimasi Proses', 'description' => 'Meningkatkan efisiensi operasional Q3 2025'],
        'roadmap' => ['title' => 'Implementasi SOP Baru', 'status' => 'in_progress', 'target' => 'Akhir Juli 2025'],
        'plan' => ['title' => 'Review dokumen procurement', 'submitted_at' => '7 Jul 2025, 08:30', 'status' => 'submitted'],
        'realization' => ['title' => 'Belum diisi', 'submitted_at' => null, 'status' => 'missing'],
    ];
    $triggeredRules = [
        ['code' => 'RULE-001', 'description' => 'Missing submission > 2 hari berturut-turut', 'severity' => 'major'],
        ['code' => 'RULE-007', 'description' => 'Roadmap stagnant > 7 hari', 'severity' => 'medium'],
    ];
    $timestamps = [
        'plan_submitted' => '7 Jul 2025, 08:30',
        'window_plan' => '08:00 – 17:00',
        'window_realization' => '15:00 – 23:59',
        'detected' => '7 Jul 2025, 17:05',
    ];
@endphp

<div class="space-y-5">
    {{-- Finding header --}}
    <div>
        <div class="flex items-start justify-between gap-2">
            <h4 class="text-base font-semibold text-text" style="font-family: 'DM Sans', sans-serif;">{{ $finding['title'] }}</h4>
            <x-ui.severity-badge :severity="$finding['severity']" />
        </div>
        <p class="text-sm text-muted mt-1">{{ $finding['user'] }} · {{ $finding['division'] }} · {{ $finding['date'] }}</p>
    </div>

    {{-- Hierarchy Chain: Big Rock → Roadmap → Plan → Realization --}}
    <div>
        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Hierarki</p>

        {{-- Big Rock --}}
        <div class="bg-primary-light border border-primary/20 rounded-lg p-3">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <p class="text-xs font-semibold text-primary uppercase tracking-wide">Big Rock</p>
            </div>
            <p class="text-sm font-medium text-text">{{ $hierarchy['big_rock']['title'] }}</p>
            <p class="text-xs text-muted mt-0.5">{{ $hierarchy['big_rock']['description'] }}</p>
        </div>

        <div class="flex justify-center py-1"><svg class="w-4 h-4 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>

        {{-- Roadmap --}}
        <div class="bg-app-bg border border-border rounded-lg p-3">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-1">Roadmap</p>
            <p class="text-sm font-medium text-text">{{ $hierarchy['roadmap']['title'] }}</p>
            <div class="flex items-center gap-2 mt-1.5">
                <x-ui.status-badge :status="$hierarchy['roadmap']['status']" />
                <span class="text-xs text-muted">Target: {{ $hierarchy['roadmap']['target'] }}</span>
            </div>
        </div>

        <div class="flex justify-center py-1"><svg class="w-4 h-4 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>

        {{-- Plan --}}
        <div class="bg-app-bg border border-border rounded-lg p-3">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-1">Plan</p>
            <p class="text-sm font-medium text-text">{{ $hierarchy['plan']['title'] }}</p>
            <div class="flex items-center gap-2 mt-1.5">
                <x-ui.status-badge :status="$hierarchy['plan']['status']" />
                @if($hierarchy['plan']['submitted_at'])
                    <span class="text-xs text-muted">{{ $hierarchy['plan']['submitted_at'] }}</span>
                @endif
            </div>
        </div>

        <div class="flex justify-center py-1"><svg class="w-4 h-4 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>

        {{-- Realization --}}
        <div class="bg-app-bg border border-border rounded-lg p-3">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-1">Realization</p>
            <p class="text-sm font-medium text-text">{{ $hierarchy['realization']['title'] }}</p>
            <div class="flex items-center gap-2 mt-1.5">
                <x-ui.status-badge :status="$hierarchy['realization']['status']" />
                @if($hierarchy['realization']['submitted_at'])
                    <span class="text-xs text-muted">{{ $hierarchy['realization']['submitted_at'] }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Triggered Rules --}}
    <div>
        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Triggered Rules</p>
        <div class="space-y-2">
            @foreach($triggeredRules as $rule)
                @php
                    $ruleBg = $rule['severity'] === 'major' ? 'bg-danger-bg' : ($rule['severity'] === 'medium' ? 'bg-warning-bg' : 'bg-info-bg');
                @endphp
                <div class="flex items-start gap-2 {{ $ruleBg }} rounded-lg p-3">
                    <x-ui.severity-badge :severity="$rule['severity']" />
                    <div>
                        <p class="text-xs font-mono text-muted">{{ $rule['code'] }}</p>
                        <p class="text-sm text-text">{{ $rule['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Timestamps --}}
    <div>
        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Timestamps</p>
        <div class="grid grid-cols-2 gap-3">
            <div><p class="text-xs text-muted">Plan Submitted</p><p class="text-sm text-text">{{ $timestamps['plan_submitted'] }}</p></div>
            <div><p class="text-xs text-muted">Terdeteksi</p><p class="text-sm text-text">{{ $timestamps['detected'] }}</p></div>
            <div><p class="text-xs text-muted">Window Plan</p><p class="text-sm text-text">{{ $timestamps['window_plan'] }}</p></div>
            <div><p class="text-xs text-muted">Window Realisasi</p><p class="text-sm text-text">{{ $timestamps['window_realization'] }}</p></div>
        </div>
    </div>
</div>
