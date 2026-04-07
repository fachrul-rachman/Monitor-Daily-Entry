{{--
    HoD Daily Entry Page (content only, layout via components.layouts.app)
--}}

<div>
    <x-ui.page-header title="Entry Harian" :description="$todayDate" />

    {{-- Window Status Bar --}}
    <div class="mb-6 flex flex-wrap gap-3">
        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ $planWindowOpen ? 'bg-success-bg text-success' : 'bg-app-bg text-muted' }}">
            <span class="w-2 h-2 rounded-full {{ $planWindowOpen ? 'bg-success' : 'bg-muted' }}"></span>
            <span>Plan: {{ $planWindowInfo }}</span>
            <span class="font-semibold">
                @if($planWindowOpen)
                    Terbuka
                @elseif($planWindowBefore)
                    Belum dibuka
                @else
                    Tertutup
                @endif
            </span>
        </div>
        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ $realizationWindowOpen ? 'bg-success-bg text-success' : 'bg-app-bg text-muted' }}">
            <span class="w-2 h-2 rounded-full {{ $realizationWindowOpen ? 'bg-success' : 'bg-muted' }}"></span>
            <span>Realisasi: {{ $realizationWindowInfo }}</span>
            <span class="font-semibold">
                @if($realizationWindowOpen)
                    Terbuka
                @elseif($realizationWindowBefore)
                    Belum dibuka
                @else
                    Tertutup
                @endif
            </span>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: @entangle('activeTab') }">
        {{-- Tab buttons --}}
        <div class="flex border-b border-border mb-6">
            <button
                @click="$wire.switchTab('plan')"
                :class="tab === 'plan' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted hover:text-text'"
                class="px-4 py-3 text-sm min-h-[44px] transition-colors"
            >Plan</button>
            <button
                @click="$wire.switchTab('realisasi')"
                :class="tab === 'realisasi' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-muted hover:text-text'"
                class="px-4 py-3 text-sm min-h-[44px] transition-colors"
            >Realisasi</button>
        </div>

        {{-- TAB: PLAN --}}
        <div x-show="tab === 'plan'">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-text mb-3" style="font-family: 'DM Sans', sans-serif;">Rencana Hari Ini</h3>
                <p class="text-xs text-muted mb-4">
                    Rencana yang diisi di sini <span class="font-semibold">wajib terkait Big Rock</span> Anda.
                </p>

                @if($planWindowBefore)
                    <div class="rounded-xl border border-border bg-app-bg px-4 py-3 mb-4">
                        <p class="text-sm font-semibold text-text">Plan belum dibuka</p>
                        <p class="text-xs text-muted mt-1">Jam plan: {{ $planWindowInfo }}. Silakan kembali saat jam buka.</p>
                    </div>
                @else
                {{-- Daftar rencana yang sudah tersimpan hari ini --}}
                @if(! empty($items))
                    <div class="mb-4 space-y-2">
                        <p class="text-xs text-muted">Rencana yang sudah tersimpan:</p>
                        @foreach($items as $item)
                            <div class="flex items-center justify-between px-3 py-2 rounded-lg border border-border bg-app-bg">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-text truncate">{{ $item['title'] }}</p>
                                    <p class="text-[11px] text-muted truncate">
                                        {{ $item['big_rock'] ?? 'Tanpa Big Rock' }}
                                        @if($item['roadmap'])
                                            · {{ $item['roadmap'] }}
                                        @endif
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="btn-secondary text-xs px-3 py-2"
                                    wire:click="startEditPlan({{ $item['id'] }})"
                                    wire:target="startEditPlan"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="startEditPlan">Edit</span>
                                    <span wire:loading wire:target="startEditPlan">Membuka...</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-between items-center mb-3">
                    <p class="text-xs text-muted">
                        @if(! $planFormOpen)
                            Form tidak langsung muncul supaya halaman tidak penuh. Klik <span class="font-semibold">Rencana baru</span> untuk mulai.
                        @else
                            Isi form, lalu klik <span class="font-semibold">Simpan Rencana</span>.
                        @endif
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="btn-secondary text-xs px-3 py-2"
                            wire:click="startCreatePlan"
                            wire:target="startCreatePlan"
                            wire:loading.attr="disabled"
                            @disabled($planWindowBefore || empty($bigRocks))
                        >
                            <span wire:loading.remove wire:target="startCreatePlan">+ Rencana baru</span>
                            <span wire:loading wire:target="startCreatePlan">Membuka...</span>
                        </button>
                        @if($planFormOpen)
                            <button
                                type="button"
                                class="btn-secondary text-xs px-3 py-2"
                                wire:click="closePlanForm"
                                wire:target="closePlanForm"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="closePlanForm">Tutup</span>
                                <span wire:loading wire:target="closePlanForm">...</span>
                            </button>
                        @endif
                    </div>
                </div>

                @if($planWindowBefore)
                    <p class="text-xs text-muted mb-4">
                        Plan hanya dapat diisi setelah jam buka: {{ $planWindowInfo }}.
                    </p>
                @elseif(! $planFormOpen)
                    <div class="rounded-xl border border-border bg-app-bg px-4 py-3 mb-4">
                        <p class="text-sm font-semibold text-text">Belum ada form yang terbuka</p>
                        <p class="text-xs text-muted mt-1">Klik <span class="font-semibold">Rencana baru</span> untuk mulai mengisi.</p>
                    </div>
                @endif

                @if($planFormOpen && ! $planWindowBefore)
                <div class="space-y-4">
                    {{-- Big Rock --}}
                    <div>
                        <label class="label">Big Rock <span class="text-danger">*</span></label>
                        <select
                            class="input"
                            wire:model="bigRockId"
                            wire:change="selectBigRock($event.target.value)"
                            @disabled($planWindowBefore || empty($bigRocks))
                        >
                            <option value="">Pilih Big Rock...</option>
                            @foreach($bigRocks as $br)
                                <option value="{{ $br['id'] }}">{{ $br['title'] }}</option>
                            @endforeach
                        </select>
                        @if(empty($bigRocks))
                            <p class="text-xs text-warning mt-1">Belum ada Big Rock aktif. Tambahkan Big Rock terlebih dahulu.</p>
                        @endif
                        @error('bigRockId') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Roadmap --}}
                    <div>
                        <label class="label">Roadmap</label>
                        <select
                            class="input"
                            wire:model="roadmapItemId"
                            wire:key="roadmap-select-{{ $bigRockId ?? 'none' }}"
                            @disabled($planWindowBefore)
                        >
                            <option value="">Pilih Roadmap...</option>
                            @foreach($roadmapItems as $item)
                                <option value="{{ $item['id'] }}">{{ $item['title'] }}</option>
                            @endforeach
                        </select>
                        @if(! empty($bigRocks) && empty($roadmapItems) && $bigRockId)
                            <p class="text-xs text-muted mt-1">Big Rock ini belum memiliki roadmap. Anda tetap bisa mengisi plan.</p>
                        @endif
                        @error('roadmapItemId') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Judul Plan --}}
                    <div>
                        <label class="label">Judul Rencana <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="input"
                            placeholder="Contoh: Review SOP proses klaim"
                            wire:model.defer="planTitle"
                            @disabled($planWindowBefore)
                        />
                        @error('planTitle') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Deskripsi Plan --}}
                    <div>
                        <label class="label">Deskripsi Rencana</label>
                        <textarea
                            class="input min-h-[120px]"
                            placeholder="Contoh: Review SOP klaim untuk cabang utama dan identifikasi bottleneck..."
                            wire:model.defer="planText"
                            @disabled($planWindowBefore)
                        ></textarea>
                        @error('planText') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Alasan keterkaitan dengan Big Rock --}}
                    <div>
                        <label class="label">Kenapa rencana ini terkait Big Rock tersebut? <span class="text-danger">*</span></label>
                        <textarea
                            class="input min-h-[80px]"
                            placeholder="Contoh: Review ini bagian dari tahapan implementasi SOP baru di seluruh cabang..."
                            wire:model.defer="planRelationReason"
                            @disabled($planWindowBefore)
                        ></textarea>
                        @error('planRelationReason') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if($storedPlanStatus)
                        <p class="text-xs text-muted">
                            Status plan hari ini:
                            <span class="font-semibold text-text">{{ ucfirst($storedPlanStatus) }}</span>
                        </p>
                    @endif

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            class="btn-primary flex-1 flex items-center justify-center gap-2"
                            wire:click="savePlan"
                            wire:target="savePlan"
                            wire:loading.attr="disabled"
                            @disabled($planWindowBefore || empty($bigRocks))
                        >
                            <span wire:loading.remove wire:target="savePlan">
                                Simpan Rencana
                                @if($planWindowAfter)
                                    <span class="text-xs font-normal text-warning ml-1">(Akan dianggap Late)</span>
                                @endif
                            </span>
                            <span wire:loading wire:target="savePlan" class="flex items-center gap-2">
                                <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>

                </div>
                @endif
                @endif
            </x-ui.card>
        </div>

        {{-- TAB: REALISASI --}}
        <div x-show="tab === 'realisasi'">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-text mb-3" style="font-family: 'DM Sans', sans-serif;">Realisasi Hari Ini</h3>
                <p class="text-xs text-muted mb-4">
                    Pilih rencana yang ingin Anda evaluasi, lalu jelaskan realisasinya.
                </p>

                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-muted">
                        @if($realizationWindowBefore)
                            Realisasi belum dibuka. Anda bisa mulai mengisi saat jam {{ $realizationWindowInfo }}.
                        @elseif(empty($items) && ! $realizationFormOpen)
                            Belum ada rencana hari ini. Isi dulu di tab Plan.
                        @elseif(! $realizationFormOpen)
                            Form tidak langsung muncul supaya halaman tidak penuh. Klik <span class="font-semibold">Realisasi baru</span> untuk mulai.
                        @else
                            Pilih rencana, lalu isi realisasinya.
                        @endif
                    </p>
                    @if(! $realizationWindowBefore)
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="btn-secondary text-xs px-3 py-2"
                                wire:click="startRealization"
                                wire:target="startRealization"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="startRealization">+ Realisasi baru</span>
                                <span wire:loading wire:target="startRealization">Membuka...</span>
                            </button>
                            @if($realizationFormOpen)
                                <button
                                    type="button"
                                    class="btn-secondary text-xs px-3 py-2"
                                    wire:click="closeRealizationForm"
                                    wire:target="closeRealizationForm"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="closeRealizationForm">Tutup</span>
                                    <span wire:loading wire:target="closeRealizationForm">...</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                @if($realizationNotice)
                    <div class="rounded-xl border border-border bg-app-bg px-4 py-3 mb-4">
                        <p class="text-xs text-muted">{{ $realizationNotice }}</p>
                    </div>
                @endif

                @if($realizationWindowBefore)
                    <div class="rounded-xl border border-border bg-app-bg px-4 py-3 mb-4">
                        <p class="text-sm font-semibold text-text">Realisasi belum dibuka</p>
                        <p class="text-xs text-muted mt-1">Jam realisasi: {{ $realizationWindowInfo }}.</p>
                    </div>
                @elseif(empty($items) && ! $realizationFormOpen)
                    <div class="rounded-xl border border-border bg-app-bg px-4 py-3 mb-4">
                        <p class="text-sm font-semibold text-text">Belum ada rencana</p>
                        <p class="text-xs text-muted mt-1">Isi rencana dulu di tab Plan, lalu kembali ke sini.</p>
                    </div>
                @elseif(! $realizationFormOpen)
                    <div class="rounded-xl border border-border bg-app-bg px-4 py-3 mb-4">
                        <p class="text-sm font-semibold text-text">Belum ada form yang terbuka</p>
                        <p class="text-xs text-muted mt-1">Klik <span class="font-semibold">Realisasi baru</span> untuk mulai mengisi.</p>
                    </div>
                @endif

                @if($realizationFormOpen && ! $realizationWindowBefore)
                <div class="space-y-4">
                    {{-- Pilih rencana yang akan dievaluasi --}}
                    <div>
                        <label class="label">Pilih Rencana <span class="text-danger">*</span></label>
                        <select
                            class="input"
                            wire:model="selectedItemId"
                            wire:change="selectRealizationItem($event.target.value)"
                            @disabled($realizationWindowBefore || empty($items))
                        >
                            <option value="">Pilih rencana...</option>
                            @foreach($items as $item)
                                <option value="{{ $item['id'] }}">{{ $item['title'] }}</option>
                            @endforeach
                        </select>
                        @if(empty($items))
                            <p class="text-xs text-warning mt-1">
                                Belum ada rencana hari ini. Isi dulu di tab Plan.
                            </p>
                        @endif
                    </div>

                    {{-- Status Realisasi --}}
                    <div>
                        <label class="label">Status Realisasi <span class="text-danger">*</span></label>
                        <select
                            class="input"
                            wire:model="realizationStatus"
                            @disabled($realizationWindowBefore || empty($items) || ! $selectedItemId)
                        >
                            <option value="done">Selesai</option>
                            <option value="partial">Sebagian</option>
                            <option value="not_done">Tidak Dikerjakan</option>
                            <option value="blocked">Blocked</option>
                        </select>
                        @error('realizationStatus') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Deskripsi Realisasi --}}
                    <div>
                        <label class="label">Deskripsi Realisasi</label>
                        <textarea
                            class="input min-h-[120px]"
                            placeholder="Contoh: Review selesai 80%, ada kendala data dari tim finance..."
                            wire:model.defer="realizationText"
                            @disabled($realizationWindowBefore || empty($items) || ! $selectedItemId)
                        ></textarea>
                        @error('realizationText') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Alasan jika tidak selesai --}}
                    <div>
                        <label class="label">Alasan jika belum selesai / blocked</label>
                        <textarea
                            class="input min-h-[80px]"
                            placeholder="Contoh: Ada meeting mendadak dengan Director, pekerjaan tertunda ke besok."
                            wire:model.defer="realizationReason"
                            @disabled($realizationWindowBefore || empty($items) || ! $selectedItemId)
                        ></textarea>
                        @error('realizationReason') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Lampiran --}}
                    <div>
                        <label class="label">Lampiran (opsional, maks 50MB)</label>
                        <input
                            type="file"
                            class="input"
                            multiple
                            wire:model="realizationAttachments"
                            @disabled($realizationWindowBefore || empty($items) || ! $selectedItemId)
                        />
                        <p wire:loading wire:target="realizationAttachments" class="text-xs text-muted mt-1">
                            Mengunggah lampiran...
                        </p>
                        <p class="text-[11px] text-muted mt-1">
                            Bisa upload lebih dari 1 file. Maks 50MB per file (bukan gabungan).
                        </p>
                        @error('realizationAttachments') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                        @error('realizationAttachments.*') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror

                        @if($currentAttachmentPath)
                            <p class="text-[11px] text-muted mt-1">
                                Sudah ada lampiran tersimpan untuk rencana ini.
                            </p>
                        @endif

                        @if(! empty($existingAttachments))
                            <div class="mt-2 space-y-1">
                                @foreach($existingAttachments as $att)
                                    <p class="text-[11px] text-muted truncate">- {{ $att['name'] }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($storedRealizationStatus)
                        <p class="text-xs text-muted">
                            Status realisasi hari ini:
                            <span class="font-semibold text-text">{{ ucfirst($storedRealizationStatus) }}</span>
                        </p>
                    @endif

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            class="btn-primary flex-1 flex items-center justify-center gap-2"
                            wire:click="saveRealization"
                            wire:target="saveRealization,realizationAttachments"
                            wire:loading.attr="disabled"
                            @disabled($realizationWindowBefore || empty($items) || ! $selectedItemId)
                        >
                            <span wire:loading.remove wire:target="saveRealization">
                                Simpan Realisasi
                                @if($realizationWindowAfter)
                                    <span class="text-xs font-normal text-warning ml-1">(Akan dianggap Late)</span>
                                @endif
                            </span>
                            <span wire:loading wire:target="saveRealization" class="flex items-center gap-2">
                                <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>

                </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
