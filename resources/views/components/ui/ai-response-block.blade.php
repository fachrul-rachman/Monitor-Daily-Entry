{{--
    AI Response Block Component
    Usage: <x-ui.ai-response-block :response="$text" :points="$aiPoints" />
--}}

@props([
    'response' => '',
    'points' => [],
])

<div {{ $attributes->merge(['class' => 'bg-primary-light border border-primary/20 rounded-xl p-4']) }}>
    <div class="flex items-center gap-2 mb-3">
        <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center">
            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
        </div>
        <span class="text-sm font-semibold text-primary uppercase tracking-wide">AI Response</span>
    </div>

    @if($response)
        <div class="text-sm text-text leading-relaxed whitespace-pre-line">{{ $response }}</div>
    @endif

    @if(count($points) > 0)
        <ul class="mt-3 space-y-1">
            @foreach($points as $point)
                <li class="flex items-start gap-2 text-sm text-text">
                    <span class="text-primary mt-0.5">&rarr;</span>
                    {{ $point }}
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Slot for custom content --}}
    @if(!$response && count($points) === 0)
        {{ $slot }}
    @endif

    <p class="text-sm text-muted mt-3 pt-3 border-t border-primary/20">
        Hasil AI bersifat pendukung. Verifikasi dengan data aktual.
    </p>
</div>
