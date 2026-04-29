<?php

namespace App\Livewire\Admin;

use App\Models\BigRock;
use App\Models\DailyEntryItem;
use App\Models\DailyEntryItemAttachment;
use App\Models\Division;
use App\Models\OverrideLog;
use App\Models\RoadmapItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Override Entry')]
class OverridePage extends Component
{
    use WithPagination;
    use WithFileUploads;

    protected array $messages = [
        'newAttachments.*.uploaded' => 'Upload lampiran gagal. Biasanya karena batas maksimal upload di server masih kecil. Coba file yang lebih kecil dulu, atau minta batas upload dinaikkan agar bisa 50MB per file.',
        'newAttachments.*.max' => 'Ukuran lampiran maksimal 50MB per file.',
    ];

    #[Url]
    public string $search = '';

    #[Url]
    public string $division = '';

    #[Url]
    public string $type = ''; // plan | realisasi | ''

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public bool $drawerOpen = false;
    public ?int $selectedItemId = null;

    /** @var array<string, mixed> */
    public array $selected = [];

    // Editable fields (override per item)
    public ?int $editBigRockId = null;
    public ?int $editRoadmapItemId = null;
    public string $editPlanTitle = '';
    public string $editPlanText = '';
    public string $editPlanRelationReason = '';

    public string $editRealizationStatus = 'draft';
    public string $editRealizationText = '';
    public string $editRealizationReason = '';

    /** @var array<int, array{id:int,name:string}> */
    public array $existingAttachments = [];
    /** @var array<int, int> */
    public array $removeAttachmentIds = [];
    /** @var array<int, mixed> */
    public array $newAttachments = [];

    public string $overrideReason = '';

    /** @var array<string, mixed> */
    public array $lastAudit = [];

    /** @var array<int, array{id:int,title:string}> */
    public array $bigRockOptions = [];

    /** @var array<int, array{id:int,title:string}> */
    public array $roadmapOptions = [];

    public function mount(): void
    {
        $today = Carbon::today();
        $this->from = $this->from ?: $today->copy()->subDays(14)->toDateString();
        $this->to = $this->to ?: $today->toDateString();
        $this->normalizeDates();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingDivision(): void { $this->resetPage(); }
    public function updatingType(): void { $this->resetPage(); }
    public function updatingFrom(): void { $this->resetPage(); }
    public function updatingTo(): void { $this->resetPage(); }

    public function applyFilters(): void
    {
        $this->validate([
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $this->normalizeDates();
        $this->resetPage();
        $this->closeDrawer();
    }

    public function resetFilters(): void
    {
        $today = Carbon::today();
        $this->reset('search', 'division', 'type');
        $this->from = $today->copy()->subDays(14)->toDateString();
        $this->to = $today->toDateString();
        $this->normalizeDates();
        $this->resetPage();
        $this->closeDrawer();
    }

    protected function normalizeDates(): void
    {
        try {
            $from = Carbon::parse($this->from)->startOfDay();
            $to = Carbon::parse($this->to)->startOfDay();
        } catch (\Throwable) {
            $today = Carbon::today();
            $from = $today->copy()->subDays(14);
            $to = $today;
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($to->diffInDays($from) > 90) {
            $from = $to->copy()->subDays(90);
        }

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    public function updatedEditBigRockId(): void
    {
        $this->editRoadmapItemId = null;
        $this->loadRoadmapOptions();
    }

    public function openOverride(int $itemId): void
    {
        /** @var DailyEntryItem|null $item */
        $item = DailyEntryItem::query()
            ->with([
                'entry:id,user_id,entry_date',
                'entry.user:id,name,email,division_id',
                'entry.user.division:id,name',
                'attachments:id,daily_entry_item_id,original_name,path',
            ])
            ->where('id', $itemId)
            ->first();

        if (! $item || ! $item->entry) {
            return;
        }

        $userId = (int) $item->entry->user_id;

        $this->selectedItemId = (int) $item->id;
        $this->selected = [
            'id' => (int) $item->id,
            'date' => $item->entry?->entry_date?->translatedFormat('j M Y') ?: '-',
            'user' => $item->entry?->user?->name ?: '-',
            'email' => $item->entry?->user?->email ?: '-',
            'division' => $item->entry?->user?->division?->name ?: '-',
        ];

        $this->loadBigRockOptions($userId);
        $this->editBigRockId = $item->big_rock_id ? (int) $item->big_rock_id : null;
        $this->loadRoadmapOptions();
        $this->editRoadmapItemId = $item->roadmap_item_id ? (int) $item->roadmap_item_id : null;

        $this->editPlanTitle = (string) ($item->plan_title ?? '');
        $this->editPlanText = (string) ($item->plan_text ?? '');
        $this->editPlanRelationReason = (string) ($item->plan_relation_reason ?? '');

        $this->editRealizationStatus = (string) ($item->realization_status ?? 'draft');
        $this->editRealizationText = (string) ($item->realization_text ?? '');
        $this->editRealizationReason = (string) ($item->realization_reason ?? '');

        $this->existingAttachments = $item->attachments
            ->map(fn ($a) => ['id' => (int) $a->id, 'name' => (string) ($a->original_name ?: basename($a->path))])
            ->all();
        $this->removeAttachmentIds = [];
        $this->newAttachments = [];
        $this->overrideReason = '';

        $this->loadLastAudit($item);

        $this->drawerOpen = true;
    }

    protected function loadLastAudit(DailyEntryItem $item): void
    {
        $log = OverrideLog::query()
            ->with(['actor:id,name'])
            ->where('target_type', 'daily_entry_items')
            ->where('target_id', $item->id)
            ->orderByDesc('id')
            ->first();

        if (! $log) {
            $this->lastAudit = [];

            return;
        }

        $this->lastAudit = [
            'actor' => $log->actor?->name ?: '-',
            'time' => $log->created_at?->translatedFormat('j M Y, H:i') ?: '-',
            'reason' => (string) $log->reason,
            'changes' => $log->changes,
        ];
    }

    protected function loadBigRockOptions(int $userId): void
    {
        $this->bigRockOptions = BigRock::query()
            ->where('user_id', $userId)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn ($br) => ['id' => (int) $br->id, 'title' => (string) $br->title])
            ->all();
    }

    protected function loadRoadmapOptions(): void
    {
        if (! $this->editBigRockId) {
            $this->roadmapOptions = [];

            return;
        }

        $this->roadmapOptions = RoadmapItem::query()
            ->where('big_rock_id', (int) $this->editBigRockId)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn ($rm) => ['id' => (int) $rm->id, 'title' => (string) $rm->title])
            ->all();
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->selectedItemId = null;
        $this->selected = [];
        $this->lastAudit = [];

        $this->editBigRockId = null;
        $this->editRoadmapItemId = null;
        $this->editPlanTitle = '';
        $this->editPlanText = '';
        $this->editPlanRelationReason = '';
        $this->editRealizationStatus = 'draft';
        $this->editRealizationText = '';
        $this->editRealizationReason = '';
        $this->existingAttachments = [];
        $this->removeAttachmentIds = [];
        $this->newAttachments = [];

        $this->overrideReason = '';
    }

    public function saveOverride(): void
    {
        $this->validate([
            'overrideReason' => 'required|string|min:8',
            'editBigRockId' => 'required|integer|exists:big_rocks,id',
            'editRoadmapItemId' => 'nullable|integer|exists:roadmap_items,id',
            'editPlanTitle' => 'required|string|max:255',
            'editPlanText' => 'nullable|string',
            'editPlanRelationReason' => 'required|string',
            'editRealizationStatus' => 'required|string|in:draft,done,partial,not_done,blocked',
            'editRealizationText' => 'nullable|string',
            'editRealizationReason' => 'nullable|string',
            'removeAttachmentIds' => 'nullable|array',
            'removeAttachmentIds.*' => 'integer|exists:daily_entry_item_attachments,id',
            'newAttachments' => 'nullable|array',
            'newAttachments.*' => 'file|max:51200',
        ]);

        if (! $this->selectedItemId) {
            return;
        }

        /** @var DailyEntryItem|null $item */
        $item = DailyEntryItem::query()
            ->with(['entry:id,user_id,entry_date', 'attachments:id,daily_entry_item_id,path,original_name'])
            ->where('id', $this->selectedItemId)
            ->first();

        if (! $item || ! $item->entry) {
            return;
        }

        $ownerUserId = (int) $item->entry->user_id;

        $ownsBigRock = BigRock::query()
            ->where('id', (int) $this->editBigRockId)
            ->where('user_id', $ownerUserId)
            ->exists();

        if (! $ownsBigRock) {
            $this->dispatch('toast', message: 'Big Rock tidak valid untuk user ini.', type: 'danger');

            return;
        }

        if ($this->editRoadmapItemId) {
            $roadmapValid = RoadmapItem::query()
                ->where('id', (int) $this->editRoadmapItemId)
                ->where('big_rock_id', (int) $this->editBigRockId)
                ->exists();

            if (! $roadmapValid) {
                $this->dispatch('toast', message: 'Roadmap tidak valid untuk Big Rock yang dipilih.', type: 'danger');

                return;
            }
        }

        // Realisasi: kalau status bukan done, reason wajib (sesuai pola input user).
        if ($this->editRealizationStatus !== 'done' && $this->editRealizationStatus !== 'draft' && trim($this->editRealizationReason) === '') {
            $this->dispatch('toast', message: 'Alasan realisasi wajib diisi jika status bukan Done.', type: 'danger');

            return;
        }

        $now = now();

        $before = [
            'big_rock_id' => $item->big_rock_id,
            'roadmap_item_id' => $item->roadmap_item_id,
            'plan_title' => $item->plan_title,
            'plan_text' => $item->plan_text,
            'plan_relation_reason' => $item->plan_relation_reason,
            'realization_status' => $item->realization_status,
            'realization_text' => $item->realization_text,
            'realization_reason' => $item->realization_reason,
            'attachments' => $item->attachments->map(fn ($a) => ['id' => (int) $a->id, 'path' => (string) $a->path, 'name' => (string) ($a->original_name ?: basename($a->path))])->all(),
        ];

        $item->big_rock_id = (int) $this->editBigRockId;
        $item->roadmap_item_id = $this->editRoadmapItemId ? (int) $this->editRoadmapItemId : null;
        $item->plan_title = $this->editPlanTitle;
        $item->plan_text = $this->editPlanText;
        $item->plan_relation_reason = $this->editPlanRelationReason;
        $item->realization_status = $this->editRealizationStatus;
        $item->realization_text = $this->editRealizationText;
        $item->realization_reason = $this->editRealizationReason;
        $item->save();

        if (! empty($this->removeAttachmentIds)) {
            $toRemove = DailyEntryItemAttachment::query()
                ->where('daily_entry_item_id', $item->id)
                ->whereIn('id', $this->removeAttachmentIds)
                ->get(['id', 'path']);

            foreach ($toRemove as $att) {
                try {
                    if ($att->path) {
                        Storage::delete($att->path);
                    }
                } catch (\Throwable) {
                    // ignore
                }

                $att->delete();
            }
        }

        if (! empty($this->newAttachments)) {
            foreach ($this->newAttachments as $upload) {
                $path = $upload->store('daily-entry-attachments/'.$item->id.'/override-'.$now->format('YmdHis'));
                
                DailyEntryItemAttachment::query()->create([
                    'daily_entry_item_id' => $item->id,
                    'path' => $path,
                    'original_name' => $upload->getClientOriginalName(),
                    'mime_type' => $upload->getMimeType(),
                    'size_bytes' => $upload->getSize(),
                ]);
            }
        }

        $item->refresh();
        $item->loadMissing(['attachments:id,daily_entry_item_id,path,original_name']);

        $after = [
            'big_rock_id' => $item->big_rock_id,
            'roadmap_item_id' => $item->roadmap_item_id,
            'plan_title' => $item->plan_title,
            'plan_text' => $item->plan_text,
            'plan_relation_reason' => $item->plan_relation_reason,
            'realization_status' => $item->realization_status,
            'realization_text' => $item->realization_text,
            'realization_reason' => $item->realization_reason,
            'attachments' => $item->attachments->map(fn ($a) => ['id' => (int) $a->id, 'path' => (string) $a->path, 'name' => (string) ($a->original_name ?: basename($a->path))])->all(),
        ];

        OverrideLog::query()->create([
            'actor_user_id' => auth()->id(),
            'target_type' => 'daily_entry_items',
            'target_id' => $item->id,
            'context_date' => $item->entry?->entry_date?->toDateString(),
            'reason' => $this->overrideReason,
            'changes' => [
                'before' => $before,
                'after' => $after,
            ],
        ]);

        $this->openOverride((int) $item->id);
        $this->dispatch('toast', message: 'Override detail tersimpan dan tercatat.', type: 'success');
    }

    public function render()
    {
        $from = Carbon::parse($this->from)->toDateString();
        $to = Carbon::parse($this->to)->toDateString();

        $q = DailyEntryItem::query()
            ->with([
                'entry:id,user_id,entry_date',
                'entry.user:id,name,email,division_id',
                'entry.user.division:id,name',
                'bigRock:id,title',
                'roadmapItem:id,title',
            ])
            ->whereHas('entry', function ($e) use ($from, $to) {
                $e->whereDate('entry_date', '>=', $from)
                    ->whereDate('entry_date', '<=', $to);
            });

        if ($this->division !== '') {
            $q->whereHas('entry.user', fn ($u) => $u->where('division_id', (int) $this->division));
        }

        if (trim($this->search) !== '') {
            $s = trim($this->search);
            $q->where(function ($qq) use ($s) {
                $qq->whereHas('entry.user', function ($u) use ($s) {
                    $u->where('name', 'ilike', '%'.$s.'%')
                        ->orWhere('email', 'ilike', '%'.$s.'%');
                })->orWhere('plan_title', 'ilike', '%'.$s.'%');
            });
        }

        if ($this->type === 'plan') {
            $q->where(function ($qq) {
                $qq->whereNull('big_rock_id')
                    ->orWhere('plan_title', '=', '')
                    ->orWhereNull('plan_title')
                    ->orWhere('plan_relation_reason', '=', '')
                    ->orWhereNull('plan_relation_reason');
            });
        } elseif ($this->type === 'realisasi') {
            $q->where(function ($qq) {
                $qq->whereIn('realization_status', ['blocked', 'not_done', 'partial'])
                    ->orWhere(function ($qq2) {
                        $qq2->whereIn('realization_status', ['blocked', 'not_done', 'partial'])
                            ->whereNull('realization_reason');
                    });
            });
        }

        $paged = $q->orderByDesc('id')->paginate(10);

        $items = $paged->map(function (DailyEntryItem $it) {
            return [
                'id' => (int) $it->id,
                'date' => $it->entry?->entry_date?->translatedFormat('j M Y') ?: '-',
                'user' => $it->entry?->user?->name ?: '-',
                'division' => $it->entry?->user?->division?->name ?: '-',
                'plan_title' => (string) ($it->plan_title ?: '-'),
                'big_rock' => (string) ($it->bigRock?->title ?: '-'),
                'roadmap' => (string) ($it->roadmapItem?->title ?: '-'),
                'realization_status' => (string) ($it->realization_status ?: 'draft'),
            ];
        })->all();

        $divisionOptions = Division::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['id' => (int) $d->id, 'name' => (string) $d->name])
            ->all();

        return view('livewire.admin.override-page', [
            'entries' => $paged,
            'items' => $items,
            'divisionOptions' => $divisionOptions,
        ])->layout('components.layouts.app', [
            'title' => 'Override Entry',
        ]);
    }
}

