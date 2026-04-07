{{-- Page Header Component
     Usage: <x-ui.page-header title="Users" description="Kelola akun pengguna">
               <x-slot:actions>
                   <button class="btn-primary">Tambah User</button>
               </x-slot:actions>
            </x-ui.page-header>
--}}

@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text" style="font-family: 'DM Sans', sans-serif;">
                {{ $title }}
            </h1>
            @if($description)
                <p class="text-sm text-muted mt-1">{{ $description }}</p>
            @endif
        </div>
        @if(isset($actions))
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
