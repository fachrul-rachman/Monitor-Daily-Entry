{{-- Empty State Component
     Usage: <x-ui.empty-state
               icon="document"
               title="Belum ada data"
               description="Deskripsi singkat."
               :cta-label="'Tambah Sekarang'"
               :cta-action="'openModal'"
            />
--}}

@props([
    'icon' => 'document',
    'title' => 'Belum ada data',
    'description' => '',
    'ctaLabel' => null,
    'ctaAction' => null,
])

@php
$icons = [
    'document' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>',
    'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'bell' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
    'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
];
$svgPath = $icons[$icon] ?? $icons['document'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-16 text-center']) }}>
    <div class="w-12 h-12 rounded-full bg-app-bg flex items-center justify-center mb-4">
        <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $svgPath !!}</svg>
    </div>
    <p class="text-base font-semibold text-text">{{ $title }}</p>
    @if($description)
        <p class="text-sm text-muted mt-1 max-w-xs">{{ $description }}</p>
    @endif
    @if($ctaLabel)
        {{-- TODO: Hubungkan $ctaAction ke wire:click atau href --}}
        <button class="mt-4 btn-primary" @if($ctaAction) wire:click="{{ $ctaAction }}" @endif>
            {{ $ctaLabel }}
        </button>
    @endif
</div>
