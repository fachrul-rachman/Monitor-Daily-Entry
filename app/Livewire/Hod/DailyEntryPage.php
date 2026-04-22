<?php

namespace App\Livewire\Hod;

use App\Jobs\ComputeUserMetrics;
use App\Models\BigRock;
use App\Models\DailyEntry;
use App\Models\DailyEntryItem;
use App\Models\DailyEntryItemAttachment;
use App\Models\ReportSetting;
use App\Models\RoadmapItem;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class DailyEntryPage extends Component
{
    use WithFileUploads;

    protected array $messages = [
        'realizationAttachments.*.uploaded' => 'Upload lampiran gagal. Biasanya karena batas maksimal upload di server masih kecil. Coba file yang lebih kecil dulu, atau minta batas upload dinaikkan agar bisa 50MB per file.',
        'realizationAttachments.*.max' => 'Ukuran lampiran maksimal 50MB per file.',
    ];

    public int $userId;
    public string $todayDate;

    public string $planWindowInfo;

    public string $realizationWindowInfo;

    public bool $planWindowOpen = false;

    public bool $planWindowBefore = false;

    public bool $planWindowAfter = false;

    public bool $realizationWindowOpen = false;

    public bool $realizationWindowBefore = false;

    public bool $realizationWindowAfter = false;

    public string $activeTab = 'plan';

    public bool $planFormOpen = false;
    public bool $realizationFormOpen = false;
    public string $openPlanCard = '';
    public ?string $realizationNotice = null;

    // Editing plan item
    public ?int $editingItemId = null;
    public string $planTitle = '';
    public string $planText = '';
    public string $planRelationReason = '';
    public $bigRockId = null;
    public $roadmapItemId = null;

    // Realization for selected item
    public ?int $selectedItemId = null;
    public string $realizationStatus = 'done';
    public string $realizationText = '';
    public string $realizationReason = '';
    public array $realizationAttachments = [];
    public ?string $currentAttachmentPath = null;
    public array $existingAttachments = [];

    public ?string $storedPlanStatus = null;
    public ?string $storedRealizationStatus = null;

    public array $bigRocks = [];
    public array $roadmapItems = [];
    public array $items = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->userId = $user->id;
        $today = Carbon::today();

        $this->todayDate = $today->translatedFormat('l, j F Y');

        $setting = ReportSetting::current();

        $this->planWindowInfo = sprintf(
            '%s - %s',
            Carbon::parse($setting->plan_open_time)->format('H:i'),
            Carbon::parse($setting->plan_close_time)->format('H:i'),
        );

        $this->realizationWindowInfo = sprintf(
            '%s - %s',
            Carbon::parse($setting->realization_open_time)->format('H:i'),
            Carbon::parse($setting->realization_close_time)->format('H:i'),
        );

        $now = Carbon::now();
        $this->computeWindowStates($now, $setting);

        $entry = DailyEntry::firstOrCreate(
            [
                'user_id' => $user->id,
                'entry_date' => $today->toDateString(),
            ],
        );

        $this->bigRocks = BigRock::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn ($br) => ['id' => $br->id, 'title' => $br->title])
            ->all();

        $this->storedPlanStatus = $entry->plan_status;
        $this->storedRealizationStatus = $entry->realization_status;

        $this->loadItems($entry);
        $this->loadRoadmapItems();
    }

    protected function computeWindowStates(Carbon $now, ReportSetting $setting): void
    {
        $date = $now->toDateString();

        $planOpen = Carbon::parse($date.' '.$setting->plan_open_time);
        $planClose = Carbon::parse($date.' '.$setting->plan_close_time);
        $realOpen = Carbon::parse($date.' '.$setting->realization_open_time);
        $realClose = Carbon::parse($date.' '.$setting->realization_close_time);

        // Plan
        $this->planWindowBefore = $now->lt($planOpen);
        $this->planWindowAfter = $now->gt($planClose);
        $this->planWindowOpen = ! $this->planWindowBefore && ! $this->planWindowAfter;

        // Realization
        $this->realizationWindowBefore = $now->lt($realOpen);
        $this->realizationWindowAfter = $now->gt($realClose);
        $this->realizationWindowOpen = ! $this->realizationWindowBefore && ! $this->realizationWindowAfter;

        // UX guard: sebelum jam buka, form tidak boleh dibuka sama sekali.
        if ($this->planWindowBefore) {
            $this->planFormOpen = false;
            $this->openPlanCard = '';
        }

        if ($this->realizationWindowBefore) {
            $this->realizationFormOpen = false;
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['plan', 'realisasi'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function updatedBigRockId(): void
    {
        $this->roadmapItemId = null;
        $this->loadRoadmapItems();
    }

    public function selectBigRock(mixed $value): void
    {
        $this->bigRockId = $value !== '' ? $value : null;
        $this->roadmapItemId = null;
        $this->loadRoadmapItems();
    }

    public function updated(string $name, mixed $value): void
    {
        if ($name === 'bigRockId') {
            $this->roadmapItemId = null;
            $this->loadRoadmapItems();
        }
    }

    public function updatedSelectedItemId(): void
    {
        $this->loadRealizationFromSelectedItem();
    }

    protected function loadRoadmapItems(): void
    {
        if (! $this->bigRockId) {
            $this->roadmapItems = [];

            return;
        }

        $bigRockId = (int) $this->bigRockId;

        $currentUserId = auth()->id();

        $ownsBigRock = BigRock::where('id', $bigRockId)
            ->where('user_id', $currentUserId)
            ->exists();

        if (! $ownsBigRock) {
            $this->roadmapItems = [];

            return;
        }

        $this->roadmapItems = RoadmapItem::where('big_rock_id', $bigRockId)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn ($rm) => ['id' => $rm->id, 'title' => $rm->title])
            ->all();
    }

    protected function loadItems(DailyEntry $entry): void
    {
        $this->items = $entry->items()
            ->with(['bigRock', 'roadmapItem'])
            ->orderBy('id')
            ->get()
            ->map(function (DailyEntryItem $item) {
                return [
                    'id' => $item->id,
                    'title' => $item->plan_title,
                    'big_rock' => $item->bigRock?->title,
                    'roadmap' => $item->roadmapItem?->title,
                    'status' => $item->realization_status,
                ];
            })
            ->all();

        if (! empty($this->items) && $this->selectedItemId === null) {
            $this->selectedItemId = $this->items[0]['id'];
            $this->loadRealizationFromSelectedItem();
        }
    }

    public function startCreatePlan(): void
    {
        if ($this->planWindowBefore) {
            return;
        }

        $this->planFormOpen = true;
        $this->openPlanCard = 'new';

        if ($this->bigRockId || $this->planTitle || $this->planRelationReason) {
            $this->savePlan();
        }

        $this->editingItemId = null;
        $this->planTitle = '';
        $this->planText = '';
        $this->planRelationReason = '';
        $this->bigRockId = null;
        $this->roadmapItemId = null;
        $this->loadRoadmapItems();
    }

    public function startEditPlan(int $itemId): void
    {
        $item = DailyEntryItem::find($itemId);

        if (! $item) {
            return;
        }

        $this->planFormOpen = true;
        $this->openPlanCard = (string) $itemId;

        $this->editingItemId = $item->id;
        $this->bigRockId = $item->big_rock_id;
        $this->loadRoadmapItems();
        $this->roadmapItemId = $item->roadmap_item_id;
        $this->planTitle = $item->plan_title;
        $this->planText = $item->plan_text ?? '';
        $this->planRelationReason = $item->plan_relation_reason ?? '';
    }

    public function closePlanForm(): void
    {
        $this->planFormOpen = false;
        $this->openPlanCard = '';
        $this->editingItemId = null;
        $this->planTitle = '';
        $this->planText = '';
        $this->planRelationReason = '';
        $this->bigRockId = null;
        $this->roadmapItemId = null;
        $this->loadRoadmapItems();
    }

    public function startRealization(): void
    {
        if ($this->realizationWindowBefore) {
            return;
        }

        if (empty($this->items)) {
            $this->realizationNotice = 'Belum ada rencana hari ini. Isi rencana dulu di tab Plan, lalu kembali ke Realisasi.';
            $this->activeTab = 'plan';

            return;
        }

        $this->realizationFormOpen = true;
        $this->realizationNotice = null;

        if (! $this->selectedItemId && ! empty($this->items)) {
            $this->selectedItemId = $this->items[0]['id'];
            $this->loadRealizationFromSelectedItem();
        }
    }

    public function selectRealizationItem($value): void
    {
        if ($this->realizationWindowBefore) {
            return;
        }

        $this->selectedItemId = $value ? (int) $value : null;
        $this->realizationFormOpen = true;
        $this->loadRealizationFromSelectedItem();
    }

    public function closeRealizationForm(): void
    {
        $this->realizationFormOpen = false;
    }

    protected function loadRealizationFromSelectedItem(): void
    {
        if (! $this->selectedItemId) {
            $this->realizationStatus = 'done';
            $this->realizationText = '';
            $this->realizationReason = '';
            $this->currentAttachmentPath = null;
            $this->existingAttachments = [];

            return;
        }

        $item = DailyEntryItem::find($this->selectedItemId);

        if (! $item) {
            return;
        }

        $this->realizationStatus = $item->realization_status !== 'draft'
            ? $item->realization_status
            : 'done';
        $this->realizationText = $item->realization_text ?? '';
        $this->realizationReason = $item->realization_reason ?? '';
        $this->currentAttachmentPath = $item->realization_attachment_path;
        $this->existingAttachments = $item->attachments()
            ->orderBy('id')
            ->get(['id', 'original_name', 'path'])
            ->map(fn ($a) => ['id' => $a->id, 'name' => $a->original_name ?: basename($a->path)])
            ->all();
    }

    public function savePlan(): void
    {
        $this->validate([
            'planTitle' => 'required|string|max:255',
            'bigRockId' => 'required|integer|exists:big_rocks,id',
            'planRelationReason' => 'required|string',
        ]);

        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $setting = ReportSetting::current();
        $now = Carbon::now();

        $planClose = Carbon::parse($today.' '.$setting->plan_close_time);

        if ($now->lt(Carbon::parse($today.' '.$setting->plan_open_time))) {
            return;
        }

        $entry = DailyEntry::firstOrCreate(
            [
                'user_id' => $user->id,
                'entry_date' => $today,
            ],
        );

        $itemData = [
            'big_rock_id' => $this->bigRockId,
            'roadmap_item_id' => $this->roadmapItemId,
            'plan_title' => $this->planTitle,
            'plan_text' => $this->planText,
            'plan_relation_reason' => $this->planRelationReason,
        ];

        if ($this->editingItemId && $now->gt($planClose)) {
            // Di atas jam tutup: tidak boleh mengedit plan lama.
            return;
        }

        if ($this->editingItemId) {
            DailyEntryItem::where('id', $this->editingItemId)
                ->where('daily_entry_id', $entry->id)
                ->update($itemData);
        } else {
            $item = DailyEntryItem::create($itemData + [
                'daily_entry_id' => $entry->id,
            ]);

            $this->editingItemId = $item->id;

            if ($this->selectedItemId === null) {
                $this->selectedItemId = $item->id;
            }
        }

        $entry->plan_submitted_at = $now;
        $entry->plan_status = $now->gt($planClose) ? 'late' : 'submitted';
        $entry->save();

        $this->storedPlanStatus = $entry->plan_status;
        $this->loadItems($entry);
        $this->computeWindowStates($now, $setting);

        $this->planFormOpen = true;
        $this->openPlanCard = $this->editingItemId ? (string) $this->editingItemId : ($this->openPlanCard ?: 'new');

        ComputeUserMetrics::dispatch(auth()->user());
    }

    public function saveRealization(): void
    {
        $rules = [
            'realizationStatus' => 'required|string|in:done,partial,not_done,blocked',
            'realizationText' => 'nullable|string',
            'realizationAttachments' => 'nullable|array',
            'realizationAttachments.*' => 'file|max:51200',
        ];

        if ($this->realizationStatus !== 'done') {
            $rules['realizationReason'] = 'required|string';
        }

        $this->validate($rules);

        if (! $this->selectedItemId) {
            return;
        }

        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $setting = ReportSetting::current();
        $now = Carbon::now();

        $realClose = Carbon::parse($today.' '.$setting->realization_close_time);

        if ($now->lt(Carbon::parse($today.' '.$setting->realization_open_time))) {
            return;
        }

        $entry = DailyEntry::firstOrCreate(
            [
                'user_id' => $user->id,
                'entry_date' => $today,
            ],
        );

        $item = DailyEntryItem::where('id', $this->selectedItemId)
            ->where('daily_entry_id', $entry->id)
            ->first();

        if (! $item) {
            return;
        }

        if (! empty($this->realizationAttachments)) {
            foreach ($this->realizationAttachments as $upload) {
                $path = $upload->store('daily-entry-attachments/'.$item->id);

                DailyEntryItemAttachment::create([
                    'daily_entry_item_id' => $item->id,
                    'path' => $path,
                    'original_name' => $upload->getClientOriginalName(),
                    'mime_type' => $upload->getMimeType(),
                    'size_bytes' => $upload->getSize(),
                ]);
            }

            // Backward compatible (simpan attachment pertama juga di kolom legacy)
            if (! $item->realization_attachment_path) {
                $first = DailyEntryItemAttachment::where('daily_entry_item_id', $item->id)->orderBy('id')->first();
                if ($first) {
                    $item->realization_attachment_path = $first->path;
                }
            }
        }

        $item->realization_status = $this->realizationStatus;
        $item->realization_text = $this->realizationText;
        $item->realization_reason = $this->realizationReason;
        $item->save();

        $this->realizationAttachments = [];

        $entry->realization_submitted_at = $now;
        // daily_entries.realization_status = status pelaporan (submitted/late/missing), bukan status pekerjaan item.
        $entry->realization_status = $now->gt($realClose) ? 'late' : 'submitted';
        $entry->save();

        $this->storedRealizationStatus = $entry->realization_status;
        $this->loadItems($entry);
        $this->loadRealizationFromSelectedItem();
        $this->computeWindowStates($now, $setting);

        ComputeUserMetrics::dispatch(auth()->user());
    }

    public function render()
    {
        $this->loadRoadmapItems();

        return view('livewire.hod.daily-entry-page')
            ->layout('components.layouts.app', [
                'title' => 'Entry Harian',
            ]);
    }
}
