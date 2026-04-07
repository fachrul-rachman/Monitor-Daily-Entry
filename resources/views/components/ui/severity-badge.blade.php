{{-- Severity Badge Component
     Usage: <x-ui.severity-badge :severity="$finding->severity" />
--}}

@props(['severity'])

@php
$map = [
    'major'  => ['label' => 'Major',  'class' => 'bg-danger-bg text-danger border border-danger/20'],
    'medium' => ['label' => 'Medium', 'class' => 'bg-warning-bg text-warning border border-warning/20'],
    'minor'  => ['label' => 'Minor',  'class' => 'bg-info-bg text-info border border-info/20'],
    'high'   => ['label' => 'High',   'class' => 'bg-danger-bg text-danger border border-danger/20'],
    'low'    => ['label' => 'Low',    'class' => 'bg-info-bg text-info border border-info/20'],
];

$config = $map[$severity] ?? ['label' => ucfirst($severity), 'class' => 'bg-app-bg text-muted border border-border'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold ' . $config['class']]) }}>
    ● {{ $config['label'] }}
</span>
