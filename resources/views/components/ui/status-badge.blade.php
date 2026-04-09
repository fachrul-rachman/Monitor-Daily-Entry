{{-- Status Badge Component
     Usage: <x-ui.status-badge :status="$model->status" />
--}}

@props(['status'])

@php
$map = [
    'active'      => ['label' => 'Aktif',            'class' => 'badge-success'],
    'inactive'    => ['label' => 'Non Aktif',         'class' => 'badge-danger'],
    'archived'    => ['label' => 'Diarsipkan',        'class' => 'badge-muted'],
    'submitted'   => ['label' => 'Submitted',         'class' => 'badge-primary'],
    'draft'       => ['label' => 'Draft',             'class' => 'badge-warning'],
    'late'        => ['label' => 'Terlambat',         'class' => 'badge-danger'],
    'missing'     => ['label' => 'Missing',           'class' => 'badge-danger'],
    'pending'     => ['label' => 'Pending',           'class' => 'badge-warning'],
    'approved'    => ['label' => 'Disetujui',         'class' => 'badge-success'],
    'rejected'    => ['label' => 'Ditolak',           'class' => 'badge-danger'],
    'cancelled'   => ['label' => 'Dibatalkan',        'class' => 'badge-muted'],
    'finished'    => ['label' => 'Selesai',           'class' => 'badge-success'],
    'in_progress' => ['label' => 'Sedang Berjalan',   'class' => 'badge-warning'],
    'blocked'     => ['label' => 'Blocked',           'class' => 'badge-danger'],
    'planned'     => ['label' => 'Planned',           'class' => 'badge-info'],
    'done'        => ['label' => 'Done',              'class' => 'badge-success'],
    'partial'     => ['label' => 'Sebagian',          'class' => 'badge-warning'],
    'not_done'    => ['label' => 'Tidak Selesai',     'class' => 'badge-danger'],
    'sent'        => ['label' => 'Terkirim',          'class' => 'badge-success'],
    'failed'      => ['label' => 'Gagal',             'class' => 'badge-danger'],
    'on_track'    => ['label' => 'On Track',          'class' => 'badge-success'],
    'at_risk'     => ['label' => 'At Risk',           'class' => 'badge-warning'],
];

$config = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-muted'];
@endphp

<span {{ $attributes->merge(['class' => $config['class']]) }}>
    {{ $config['label'] }}
</span>
