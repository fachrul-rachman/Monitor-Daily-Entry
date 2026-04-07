{{--
    HoD AI Chat Page
    Route: /hod/ai-chat
    Component: App\Livewire\Hod\AiChatPage
    Same structure as Director AI Chat, different suggested prompts
--}}

<x-layouts.app title="AI Chat">
    @php
        // HoD-specific prompts per COMPONENTS.md
        $suggestedPrompts = [
            'Siapa saja manager yang sering terlambat minggu ini?',
            'Analisis performa divisi saya 7 hari terakhir',
            'Apakah ada roadmap yang terlambat dari target?',
            'Bandingkan on-time rate antar manager',
            'Temuan apa yang paling sering muncul di divisi saya?',
            'Rekomendasi tindakan untuk manager dengan temuan terbanyak',
        ];
        $chatMessages = [
            ['role' => 'user', 'content' => 'Siapa saja manager yang sering terlambat minggu ini?'],
            ['role' => 'ai', 'content' => 'Berdasarkan data 7 hari terakhir di divisi Anda, berikut manager dengan keterlambatan submission:', 'points' => [
                'Budi Santoso: 4x late plan, 2x missing realization',
                'Rudi Hermawan: 3x late plan submission',
                'Eko Prasetyo: 1x late, namun konsisten di hari lainnya',
            ]],
            ['role' => 'user', 'content' => 'Apakah ada roadmap yang terlambat?'],
            ['role' => 'ai', 'content' => 'Ada 1 roadmap item yang menunjukkan keterlambatan signifikan:', 'points' => [
                'Big Rock "Optimasi Proses": Roadmap "Implementasi SOP Baru" — target selesai 15 Jul, progress masih 60%',
                'Rekomendasi: Alokasikan resource tambahan atau eskalasi ke daily standup',
            ]],
        ];
    @endphp

    <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-7rem)]">
        {{-- Left panel: Suggested prompts (desktop sidebar) --}}
        <aside class="hidden lg:block w-72 shrink-0">
            <x-ui.card class="h-full flex flex-col">
                <h3 class="text-sm font-semibold text-text mb-4" style="font-family: 'DM Sans', sans-serif;">Saran Pertanyaan</h3>
                <div class="space-y-2 flex-1 overflow-y-auto">
                    @foreach($suggestedPrompts as $prompt)
                        {{-- TODO: wire:click="sendPrompt('...')" --}}
                        <button class="w-full text-left px-3 py-2 text-sm text-text rounded-lg border border-border hover:bg-primary-light hover:border-primary/30 hover:text-primary transition-all">
                            {{ $prompt }}
                        </button>
                    @endforeach
                </div>
            </x-ui.card>
        </aside>

        {{-- Main chat area --}}
        <div class="flex-1 flex flex-col min-w-0">
            <x-ui.card class="flex-1 flex flex-col p-0 overflow-hidden">
                {{-- Chat header --}}
                <div class="px-5 py-3 border-b border-border flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-primary flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-text">Dayta AI</p>
                        <p class="text-xs text-muted">Analisis data divisi berbasis AI</p>
                    </div>
                </div>

                {{-- Mobile: Suggested prompts horizontal chips --}}
                <div class="block lg:hidden px-4 py-2 border-b border-border overflow-x-auto">
                    <div class="flex gap-2 pb-1" style="min-width: max-content;">
                        @foreach(array_slice($suggestedPrompts, 0, 4) as $prompt)
                            <button class="shrink-0 px-3 py-1.5 text-xs text-primary bg-primary-light border border-primary/20 rounded-full hover:bg-primary/10 transition-colors whitespace-nowrap">
                                {{ Str::limit($prompt, 40) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Messages area --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-4" id="chat-messages-hod">
                    @foreach($chatMessages as $msg)
                        @if($msg['role'] === 'user')
                            <div class="flex justify-end">
                                <div class="max-w-[80%] bg-primary text-white rounded-2xl rounded-tr-sm px-4 py-2.5">
                                    <p class="text-sm">{{ $msg['content'] }}</p>
                                </div>
                            </div>
                        @else
                            <div class="flex justify-start max-w-[85%]">
                                <x-ui.ai-response-block
                                    :response="$msg['content']"
                                    :points="$msg['points'] ?? []"
                                />
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Input area (sticky bottom) --}}
                <div class="p-4 border-t border-border bg-surface">
                    {{-- TODO: wire:submit.prevent="sendMessage" --}}
                    <form class="flex gap-2">
                        {{-- TODO: wire:model="messageInput" --}}
                        <input
                            type="text"
                            class="input flex-1"
                            placeholder="Tanyakan tentang divisi Anda..."
                            autocomplete="off"
                        />
                        <button type="submit" class="btn-primary shrink-0 px-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </form>
                    <p class="text-xs text-muted mt-2 text-center">Dayta AI menganalisis data divisi Anda. Hasil bersifat pendukung keputusan.</p>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
