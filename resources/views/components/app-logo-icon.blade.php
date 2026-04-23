@props([
    'alt' => config('app.name', 'Dayta'),
])

<img
    src="{{ asset('logo dayta.png') }}"
    alt="{{ $alt }}"
    decoding="async"
    loading="lazy"
    {{ $attributes->merge(['class' => 'object-contain']) }}
/>
