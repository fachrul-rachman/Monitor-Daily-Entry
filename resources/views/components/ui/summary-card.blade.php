{{-- Summary Card (Metric) Component
     Usage: <x-ui.summary-card
               label="Total Exception"
               value="42"
               context="Hari ini"
               border="danger"
            />
--}}

@props([
    'label',
    'value',
    'context' => null,
    'border' => null,
])

@php
$borderClass = match($border) {
    'danger'  => 'border-l-4 border-l-danger',
    'success' => 'border-l-4 border-l-success',
    'warning' => 'border-l-4 border-l-warning',
    'info'    => 'border-l-4 border-l-info',
    default   => '',
};
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface border border-border rounded-xl p-4 md:p-5 ' . $borderClass]) }}>
    <p class="text-sm text-muted font-medium">{{ $label }}</p>
    <p class="text-2xl font-bold text-text mt-1">{{ $value }}</p>
    @if($context)
        <p class="text-sm text-muted mt-1">{{ $context }}</p>
    @endif
</div>
