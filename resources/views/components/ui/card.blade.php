{{-- Card Component
     Usage: <x-ui.card> content </x-ui.card>
            <x-ui.card class="border-l-4 border-l-danger"> ... </x-ui.card>
--}}

<div {{ $attributes->merge(['class' => 'bg-surface border border-border rounded-xl p-4 md:p-5']) }}>
    {{ $slot }}
</div>
