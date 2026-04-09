<?php

namespace App\Livewire\Hod;

use App\Models\BigRock;
use App\Models\Division;
use App\Models\HodAssignment;
use App\Models\RoadmapItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Big Rock Tim')]
class TeamBigRockPage extends Component
{
    #[Url]
    public string $division = '';

    /** @var array<int, array{id:int,name:string}> */
    public array $divisionOptions = [];

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public bool $drawerOpen = false;
    public ?int $selectedBigRockId = null;

    /** @var array<string, mixed> */
    public array $selected = [];

    /** @var array<int, array<string, mixed>> */
    public array $selectedRoadmaps = [];

    public function mount(): void
    {
        $this->divisionOptions = $this->loadDivisionOptions();

        if ($this->division === '' && ! empty($this->divisionOptions)) {
            $this->division = (string) $this->divisionOptions[0]['id'];
        }

        $this->rows = $this->buildRows();
    }

    public function updatedDivision(): void
    {
        $this->closeDrawer();
        $this->rows = $this->buildRows();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    protected function loadDivisionOptions(): array
    {
        $hod = auth()->user();
        if (! $hod) {
            return [];
        }

        $ids = HodAssignment::query()
            ->where('hod_id', $hod->id)
            ->pluck('division_id')
            ->filter()
            ->values()
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($ids) && $hod->division_id) {
            $ids = [(int) $hod->division_id];
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids)) {
            return [];
        }

        return Division::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Division $d) => ['id' => (int) $d->id, 'name' => (string) $d->name])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildRows(): array
    {
        $divisionId = (int) $this->division;
        if ($divisionId <= 0) {
            return [];
        }

        $managers = User::query()
            ->where('division_id', $divisionId)
            ->where('role', 'manager')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($managers->isEmpty()) {
            return [];
        }

        $managerById = $managers->keyBy('id');
        $managerIds = $managers->pluck('id')->map(fn ($v) => (int) $v)->values()->all();

        $bigRocks = BigRock::query()
            ->whereIn('user_id', $managerIds)
            ->orderByRaw("case when status='active' then 0 when status='on_track' then 1 when status='at_risk' then 2 when status='completed' then 3 when status='archived' then 4 else 5 end")
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'title', 'description', 'status', 'start_date', 'end_date']);

        if ($bigRocks->isEmpty()) {
            return [];
        }

        $roadmapByBigRock = RoadmapItem::query()
            ->whereIn('big_rock_id', $bigRocks->pluck('id')->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'big_rock_id', 'title', 'status', 'sort_order'])
            ->groupBy('big_rock_id');

        $weights = [
            'planned' => 0.0,
            'in_progress' => 0.6,
            'blocked' => 0.3,
            'finished' => 1.0,
            'completed' => 1.0,
            'done' => 1.0,
        ];

        return $bigRocks->map(function (BigRock $br) use ($roadmapByBigRock, $weights, $managerById) {
            $roadmaps = ($roadmapByBigRock[$br->id] ?? collect())
                ->map(fn ($rm) => [
                    'id' => (int) $rm->id,
                    'title' => $rm->title,
                    'status' => $rm->status,
                    'sort_order' => (int) $rm->sort_order,
                ])
                ->values()
                ->all();

            $scoped = collect($roadmaps)->reject(fn ($rm) => ($rm['status'] ?? '') === 'archived')->values();
            $total = $scoped->count();
            $score = $scoped->map(fn ($rm) => (float) ($weights[$rm['status']] ?? 0.0))->sum();
            $progress = $total > 0 ? (int) round(($score / $total) * 100) : 0;

            $owner = $managerById->get($br->user_id);

            return [
                'id' => (int) $br->id,
                'owner' => (string) ($owner?->name ?? '-'),
                'title' => (string) $br->title,
                'description' => (string) ($br->description ?: ''),
                'status' => (string) ($br->status ?: 'active'),
                'start' => $br->start_date ? Carbon::parse($br->start_date)->translatedFormat('j M Y') : '-',
                'end' => $br->end_date ? Carbon::parse($br->end_date)->translatedFormat('j M Y') : '-',
                'roadmap_count' => $total,
                'progress' => $progress,
            ];
        })->all();
    }

    public function openDetail(int $bigRockId): void
    {
        $divisionId = (int) $this->division;
        if ($divisionId <= 0) {
            return;
        }

        $br = BigRock::query()
            ->where('id', $bigRockId)
            ->whereIn('user_id', User::query()->where('division_id', $divisionId)->where('role', 'manager')->where('status', 'active')->select('id'))
            ->first(['id', 'user_id', 'title', 'description', 'status', 'start_date', 'end_date']);

        if (! $br) {
            return;
        }

        $owner = User::query()->where('id', $br->user_id)->first(['id', 'name']);

        $this->selectedBigRockId = (int) $br->id;
        $this->selected = [
            'owner' => (string) ($owner?->name ?? '-'),
            'title' => (string) $br->title,
            'description' => (string) ($br->description ?: ''),
            'status' => (string) ($br->status ?: 'active'),
            'start' => $br->start_date ? Carbon::parse($br->start_date)->translatedFormat('j M Y') : '-',
            'end' => $br->end_date ? Carbon::parse($br->end_date)->translatedFormat('j M Y') : '-',
        ];

        $this->selectedRoadmaps = RoadmapItem::query()
            ->where('big_rock_id', $br->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'status', 'sort_order'])
            ->map(fn ($rm) => [
                'id' => (int) $rm->id,
                'title' => $rm->title,
                'status' => (string) $rm->status,
                'sort_order' => (int) $rm->sort_order,
            ])
            ->all();

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->selectedBigRockId = null;
        $this->selected = [];
        $this->selectedRoadmaps = [];
    }

    public function render()
    {
        return view('livewire.hod.team-big-rock-page', [
            'divisionOptions' => $this->divisionOptions,
            'rows' => $this->rows,
        ])->layout('components.layouts.app', [
            'title' => 'Big Rock Tim',
        ]);
    }
}

