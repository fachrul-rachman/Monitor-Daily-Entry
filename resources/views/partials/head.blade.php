<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Dayta') : config('app.name', 'Dayta') }}
</title>

@php
    $faviconVersion = null;
    try {
        $faviconPath = public_path('favicon.ico');
        $faviconVersion = is_file($faviconPath) ? filemtime($faviconPath) : null;
    } catch (\Throwable $e) {
        $faviconVersion = null;
    }
    $faviconQuery = $faviconVersion ? ('?v='.$faviconVersion) : '';
@endphp

<link rel="icon" href="/favicon.ico{{ $faviconQuery }}" sizes="any">
<link rel="icon" href="/favicon.png{{ $faviconQuery }}" type="image/png" sizes="32x32">
<link rel="icon" href="/favicon.svg{{ $faviconQuery }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png{{ $faviconQuery }}">

{{-- Design System Fonts: DM Sans (heading) + Inter (body) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Charts (ApexCharts) --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts" defer></script>
