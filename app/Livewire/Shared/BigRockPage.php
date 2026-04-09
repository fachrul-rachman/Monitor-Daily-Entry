<?php

namespace App\Livewire\Shared;

use App\Models\BigRock;
use App\Models\Division;
use App\Models\HodAssignment;
use App\Models\RoadmapItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;

class BigRockPage extends Component
{
    public bool $canManageBigRock = true;

    /** @var array<int, array<string, mixed>> */
    public array $bigRocks = [];

    public bool $bigRockModalOpen = false;
    public ?int $editingBigRockId = null;
    public string $bigRockTitle = '';
    public string $bigRockDescription = '';
    public ?string $bigRockStartDate = null;
    public ?string $bigRockEndDate = null;
    public string $bigRockStatus = 'active';

    public bool $roadmapDrawerOpen = false;
    public ?int $selectedBigRockId = null;
    public string $selectedBigRockTitle = '';

    /** @var array<int, array<string, mixed>> */
    public array $roadmapItems = [];

    public bool $roadmapModalOpen = false;
    public ?int $editingRoadmapId = null;
    public string $roadmapTitle = '';
    public string $roadmapStatus = 'planned';
    public int $roadmapSortOrder = 0;

    /** @var array<int, array<string, mixed>> */
    public array $teamBigRocks = [];

    public bool $teamRoadmapDrawerOpen = false;
    public ?int $teamSelectedBigRockId = null;
    public string $teamSelectedBigRockTitle = '';
    public string $teamSelectedOwner = '';
    public string $teamSelectedDivision = '';

    /** @var array<int, array<string, mixed>> */
    public array $teamRoadmapItems = [];

    public function mount(): void
    {
        $this->canManageBigRock = in_array(auth()->user()?->role, ['manager', 'hod'], true);
        $this->refreshBigRocks();
    }

    public function refreshBigRocks(): void
    {
        $user = auth()->user();

        $models = BigRock::query()
            ->where('user_id', $user->id)
            ->orderByRaw("case when status='active' then 0 when status='on_track' then 1 when status='at_risk' then 2 when status='completed' then 3 when status='archived' then 4 else 5 end")
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $roadmapByBigRock = RoadmapItem::query()
            ->whereIn('big_rock_id', $models->pluck('id')->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'big_rock_id', 'title', 'status', 'sort_order'])
            ->groupBy('big_rock_id');

        // Progress (bisnis): bobot sederhana per status agar terasa "bergerak"
        // - finished/completed/done: 100%
        // - in_progress: 60%
        // - blocked: 30% (ada usaha tapi terhambat)
        // - planned: 0%
        // Catatan: roadmap 'archived' dianggap tidak dihitung (keluar dari scope).
        $weights = [
            'planned' => 0.0,
            'in_progress' => 0.6,
            'blocked' => 0.3,
            'finished' => 1.0,
            'completed' => 1.0,
            'done' => 1.0,
        ];

        $this->bigRocks = $models
            ->map(function (BigRock $br) use ($roadmapByBigRock, $weights) {
                $roadmaps = ($roadmapByBigRock[$br->id] ?? collect())
                    ->map(fn ($rm) => [
                        'id' => (int) $rm->id,
                        'title' => $rm->title,
                        'status' => $rm->status,
                        'sort_order' => (int) $rm->sort_order,
                    ])
                    ->values()
                    ->all();

                $scopedRoadmaps = collect($roadmaps)->reject(fn ($rm) => ($rm['status'] ?? '') === 'archived')->values();
                $total = $scopedRoadmaps->count();
                $score = $scopedRoadmaps
                    ->map(fn ($rm) => (float) ($weights[$rm['status']] ?? 0.0))
                    ->sum();

                $progress = $total > 0 ? (int) round(($score / $total) * 100) : 0;

                return [
                    'id' => (int) $br->id,
                    'title' => $br->title,
                    'description' => $br->description ?: '',
                    'start' => $br->start_date ? Carbon::parse($br->start_date)->translatedFormat('j M Y') : '-',
                    'end' => $br->end_date ? Carbon::parse($br->end_date)->translatedFormat('j M Y') : '-',
                    'status' => $br->status ?: 'active',
                    'roadmap_count' => $total,
                    'progress' => $progress,
                ];
            })
            ->values()
            ->all();

        // HoD: tampilkan Big Rock Manager (read-only) dalam scope divisi yang dipegang.
        $this->teamBigRocks = [];
        if ($user && $user->role === 'hod') {
            $this->refreshTeamBigRocks($weights);
        }

        // Jika sedang membuka drawer, sync title & items.
        if ($this->selectedBigRockId) {
            $selected = $models->firstWhere('id', $this->selectedBigRockId);
            if (! $selected) {
                $this->closeRoadmapDrawer();
            }
        }
    }

    /**
     * @param array<string, float> $weights
     */
    protected function refreshTeamBigRocks(array $weights): void
    {
        $hod = auth()->user();
        if (! $hod) {
            $this->teamBigRocks = [];
            return;
        }

        $divisionIds = HodAssignment::query()
            ->where('hod_id', $hod->id)
            ->pluck('division_id')
            ->filter()
            ->values()
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($divisionIds) && $hod->division_id) {
            $divisionIds = [(int) $hod->division_id];
        }

        $divisionIds = array_values(array_unique(array_filter($divisionIds)));
        if (empty($divisionIds)) {
            $this->teamBigRocks = [];
            return;
        }

        $managerRows = User::query()
            ->whereIn('division_id', $divisionIds)
            ->where('role', 'manager')
            ->where('status', 'active')
            ->get(['id', 'name', 'division_id']);

        if ($managerRows->isEmpty()) {
            $this->teamBigRocks = [];
            return;
        }

        $managerIds = $managerRows->pluck('id')->map(fn ($v) => (int) $v)->values()->all();

        $divisionNameById = Division::query()
            ->whereIn('id', $divisionIds)
            ->pluck('name', 'id')
            ->all();

        $bigRockModels = BigRock::query()
            ->whereIn('user_id', $managerIds)
            ->orderByRaw("case when status='active' then 0 when status='on_track' then 1 when status='at_risk' then 2 when status='completed' then 3 when status='archived' then 4 else 5 end")
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'title', 'description', 'status', 'start_date', 'end_date']);

        if ($bigRockModels->isEmpty()) {
            $this->teamBigRocks = [];
            return;
        }

        $roadmapByBigRock = RoadmapItem::query()
            ->whereIn('big_rock_id', $bigRockModels->pluck('id')->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'big_rock_id', 'title', 'status', 'sort_order'])
            ->groupBy('big_rock_id');

        $managerById = $managerRows->keyBy('id');

        $this->teamBigRocks = $bigRockModels
            ->map(function (BigRock $br) use ($roadmapByBigRock, $weights, $managerById, $divisionNameById) {
                $mgr = $managerById->get($br->user_id);
                $divisionName = $mgr && $mgr->division_id ? ($divisionNameById[$mgr->division_id] ?? '-') : '-';

                $roadmaps = ($roadmapByBigRock[$br->id] ?? collect())
                    ->map(fn ($rm) => [
                        'id' => (int) $rm->id,
                        'title' => $rm->title,
                        'status' => $rm->status,
                        'sort_order' => (int) $rm->sort_order,
                    ])
                    ->values()
                    ->all();

                $scopedRoadmaps = collect($roadmaps)->reject(fn ($rm) => ($rm['status'] ?? '') === 'archived')->values();
                $total = $scopedRoadmaps->count();
                $score = $scopedRoadmaps
                    ->map(fn ($rm) => (float) ($weights[$rm['status']] ?? 0.0))
                    ->sum();
                $progress = $total > 0 ? (int) round(($score / $total) * 100) : 0;

                return [
                    'id' => (int) $br->id,
                    'owner_id' => (int) $br->user_id,
                    'owner_name' => (string) ($mgr?->name ?? '-'),
                    'division' => (string) $divisionName,
                    'title' => $br->title,
                    'description' => $br->description ?: '',
                    'start' => $br->start_date ? Carbon::parse($br->start_date)->translatedFormat('j M Y') : '-',
                    'end' => $br->end_date ? Carbon::parse($br->end_date)->translatedFormat('j M Y') : '-',
                    'status' => $br->status ?: 'active',
                    'roadmap_count' => $total,
                    'progress' => $progress,
                ];
            })
            ->values()
            ->all();

        if ($this->teamSelectedBigRockId) {
            $exists = $bigRockModels->firstWhere('id', $this->teamSelectedBigRockId);
            if (! $exists) {
                $this->closeTeamRoadmapDrawer();
            }
        }
    }

    public function openTeamRoadmapDrawer(int $bigRockId): void
    {
        $hod = auth()->user();
        if (! $hod || $hod->role !== 'hod') {
            return;
        }

        $divisionIds = HodAssignment::query()
            ->where('hod_id', $hod->id)
            ->pluck('division_id')
            ->filter()
            ->values()
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($divisionIds) && $hod->division_id) {
            $divisionIds = [(int) $hod->division_id];
        }

        $divisionIds = array_values(array_unique(array_filter($divisionIds)));
        if (empty($divisionIds)) {
            return;
        }

        $br = BigRock::query()
            ->where('id', $bigRockId)
            ->whereIn('user_id', User::query()->whereIn('division_id', $divisionIds)->where('role', 'manager')->where('status', 'active')->select('id'))
            ->first(['id', 'user_id', 'title']);

        if (! $br) {
            return;
        }

        $owner = User::query()
            ->where('id', $br->user_id)
            ->first(['id', 'name', 'division_id']);

        $divisionName = '-';
        if ($owner && $owner->division_id) {
            $divisionName = (string) (Division::query()->where('id', $owner->division_id)->value('name') ?? '-');
        }

        $this->teamSelectedBigRockId = (int) $br->id;
        $this->teamSelectedBigRockTitle = (string) $br->title;
        $this->teamSelectedOwner = (string) ($owner?->name ?? '-');
        $this->teamSelectedDivision = $divisionName;

        $this->teamRoadmapItems = RoadmapItem::query()
            ->where('big_rock_id', $br->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'status', 'sort_order'])
            ->map(fn ($rm) => [
                'id' => (int) $rm->id,
                'title' => $rm->title,
                'status' => $rm->status,
                'sort_order' => (int) $rm->sort_order,
            ])
            ->all();

        $this->teamRoadmapDrawerOpen = true;
    }

    public function closeTeamRoadmapDrawer(): void
    {
        $this->teamRoadmapDrawerOpen = false;
        $this->teamSelectedBigRockId = null;
        $this->teamSelectedBigRockTitle = '';
        $this->teamSelectedOwner = '';
        $this->teamSelectedDivision = '';
        $this->teamRoadmapItems = [];
    }

    public function openCreateBigRock(): void
    {
        if (! $this->canManageBigRock) {
            return;
        }

        $this->editingBigRockId = null;
        $this->bigRockTitle = '';
        $this->bigRockDescription = '';
        $this->bigRockStartDate = null;
        $this->bigRockEndDate = null;
        $this->bigRockStatus = 'active';
        $this->bigRockModalOpen = true;
    }

    public function openEditBigRock(int $id): void
    {
        if (! $this->canManageBigRock) {
            return;
        }

        $user = auth()->user();
        $br = BigRock::query()->where('id', $id)->where('user_id', $user->id)->first();
        if (! $br) {
            return;
        }

        $this->editingBigRockId = (int) $br->id;
        $this->bigRockTitle = $br->title;
        $this->bigRockDescription = $br->description ?? '';
        $this->bigRockStartDate = $br->start_date ? Carbon::parse($br->start_date)->toDateString() : null;
        $this->bigRockEndDate = $br->end_date ? Carbon::parse($br->end_date)->toDateString() : null;
        $this->bigRockStatus = $br->status ?: 'active';
        $this->bigRockModalOpen = true;
    }

    public function saveBigRock(): void
    {
        if (! $this->canManageBigRock) {
            return;
        }

        $data = $this->validate([
            'bigRockTitle' => 'required|string|max:255',
            'bigRockDescription' => 'nullable|string',
            'bigRockStartDate' => 'nullable|date',
            'bigRockEndDate' => 'nullable|date|after_or_equal:bigRockStartDate',
            'bigRockStatus' => 'required|string|in:active,on_track,at_risk,completed,archived',
        ]);

        $user = auth()->user();

        if ($this->editingBigRockId) {
            BigRock::query()
                ->where('id', $this->editingBigRockId)
                ->where('user_id', $user->id)
                ->update([
                    'title' => $data['bigRockTitle'],
                    'description' => $data['bigRockDescription'] ?: null,
                    'start_date' => $data['bigRockStartDate'] ?: null,
                    'end_date' => $data['bigRockEndDate'] ?: null,
                    'status' => $data['bigRockStatus'],
                ]);
        } else {
            BigRock::create([
                'user_id' => $user->id,
                'title' => $data['bigRockTitle'],
                'description' => $data['bigRockDescription'] ?: null,
                'start_date' => $data['bigRockStartDate'] ?: null,
                'end_date' => $data['bigRockEndDate'] ?: null,
                'status' => $data['bigRockStatus'],
            ]);
        }

        $this->bigRockModalOpen = false;
        $this->editingBigRockId = null;
        $this->refreshBigRocks();
    }

    public function archiveBigRock(int $id): void
    {
        if (! $this->canManageBigRock) {
            return;
        }

        $user = auth()->user();
        BigRock::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update(['status' => 'archived']);

        if ($this->selectedBigRockId === $id) {
            $this->closeRoadmapDrawer();
        }

        $this->refreshBigRocks();
    }

    public function openRoadmapDrawer(int $bigRockId): void
    {
        $user = auth()->user();
        $br = BigRock::query()
            ->where('id', $bigRockId)
            ->where('user_id', $user->id)
            ->first();

        if (! $br) {
            return;
        }

        $this->selectedBigRockId = (int) $br->id;
        $this->selectedBigRockTitle = $br->title;
        $this->loadRoadmapItems();
        $this->roadmapDrawerOpen = true;
    }

    public function closeRoadmapDrawer(): void
    {
        $this->roadmapDrawerOpen = false;
        $this->selectedBigRockId = null;
        $this->selectedBigRockTitle = '';
        $this->roadmapItems = [];
        $this->roadmapModalOpen = false;
        $this->editingRoadmapId = null;
    }

    public function openCreateRoadmap(): void
    {
        if (! $this->canManageBigRock) {
            return;
        }

        if (! $this->selectedBigRockId) {
            return;
        }

        $this->editingRoadmapId = null;
        $this->roadmapTitle = '';
        $this->roadmapStatus = 'planned';
        $this->roadmapSortOrder = (int) (collect($this->roadmapItems)->max('sort_order') ?? 0) + 1;
        $this->roadmapModalOpen = true;
    }

    public function openEditRoadmap(int $id): void
    {
        if (! $this->canManageBigRock) {
            return;
        }

        $user = auth()->user();
        $rm = RoadmapItem::query()
            ->where('id', $id)
            ->whereIn('big_rock_id', BigRock::query()->where('user_id', $user->id)->select('id'))
            ->first();

        if (! $rm) {
            return;
        }

        $this->editingRoadmapId = (int) $rm->id;
        $this->roadmapTitle = $rm->title;
        $this->roadmapStatus = $rm->status ?: 'planned';
        $this->roadmapSortOrder = (int) $rm->sort_order;
        $this->roadmapModalOpen = true;
    }

    public function saveRoadmap(): void
    {
        if (! $this->canManageBigRock) {
            return;
        }

        if (! $this->selectedBigRockId) {
            return;
        }

        $data = $this->validate([
            'roadmapTitle' => 'required|string|max:255',
            'roadmapStatus' => 'required|string|in:planned,in_progress,blocked,finished,archived',
            'roadmapSortOrder' => 'required|integer|min:0|max:1000000',
        ]);

        $user = auth()->user();
        $br = BigRock::query()->where('id', $this->selectedBigRockId)->where('user_id', $user->id)->first();
        if (! $br) {
            return;
        }

        if ($this->editingRoadmapId) {
            RoadmapItem::query()
                ->where('id', $this->editingRoadmapId)
                ->where('big_rock_id', $br->id)
                ->update([
                    'title' => $data['roadmapTitle'],
                    'status' => $data['roadmapStatus'],
                    'sort_order' => $data['roadmapSortOrder'],
                ]);
        } else {
            RoadmapItem::create([
                'big_rock_id' => $br->id,
                'title' => $data['roadmapTitle'],
                'status' => $data['roadmapStatus'],
                'sort_order' => $data['roadmapSortOrder'],
            ]);
        }

        $this->roadmapModalOpen = false;
        $this->editingRoadmapId = null;
        $this->loadRoadmapItems();
        $this->refreshBigRocks();
    }

    public function archiveRoadmap(int $id): void
    {
        if (! $this->canManageBigRock) {
            return;
        }

        $user = auth()->user();
        RoadmapItem::query()
            ->where('id', $id)
            ->whereIn('big_rock_id', BigRock::query()->where('user_id', $user->id)->select('id'))
            ->update(['status' => 'archived']);

        $this->loadRoadmapItems();
        $this->refreshBigRocks();
    }

    protected function loadRoadmapItems(): void
    {
        if (! $this->selectedBigRockId) {
            $this->roadmapItems = [];
            return;
        }

        $user = auth()->user();
        $br = BigRock::query()
            ->where('id', $this->selectedBigRockId)
            ->where('user_id', $user->id)
            ->first();

        if (! $br) {
            $this->roadmapItems = [];
            return;
        }

        $this->roadmapItems = RoadmapItem::query()
            ->where('big_rock_id', $br->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'status', 'sort_order'])
            ->map(fn ($rm) => [
                'id' => (int) $rm->id,
                'title' => $rm->title,
                'status' => $rm->status,
                'sort_order' => (int) $rm->sort_order,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.shared.big-rock-page')
            ->layout('components.layouts.app', [
                'title' => 'Big Rock',
            ]);
    }
}
