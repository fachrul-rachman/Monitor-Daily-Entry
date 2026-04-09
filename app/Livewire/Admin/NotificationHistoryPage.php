<?php

namespace App\Livewire\Admin;

use App\Models\NotificationLog;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat Notifikasi')]
class NotificationHistoryPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $channel = '';

    #[Url]
    public string $type = '';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public bool $drawerOpen = false;
    public ?int $selectedId = null;

    /** @var array<string, mixed> */
    public array $selected = [];

    public function mount(): void
    {
        $today = Carbon::today();
        $this->from = $this->from ?: $today->copy()->subDays(30)->toDateString();
        $this->to = $this->to ?: $today->toDateString();
        $this->normalizeDates();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingChannel(): void { $this->resetPage(); }
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
        $this->reset('search', 'status', 'channel', 'type');
        $this->from = $today->copy()->subDays(30)->toDateString();
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
            $from = $today->copy()->subDays(30);
            $to = $today;
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($to->diffInDays($from) > 180) {
            $from = $to->copy()->subDays(180);
        }

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    public function openDetail(int $id): void
    {
        $row = NotificationLog::query()->where('id', $id)->first();
        if (! $row) {
            return;
        }

        $this->selectedId = (int) $row->id;
        $this->selected = [
            'id' => (int) $row->id,
            'sent_at' => $row->sent_at?->translatedFormat('j M Y, H:i'),
            'failed_at' => $row->failed_at?->translatedFormat('j M Y, H:i'),
            'created_at' => $row->created_at?->translatedFormat('j M Y, H:i'),
            'channel' => (string) $row->channel,
            'type' => (string) $row->type,
            'status' => (string) $row->status,
            'context_date' => $row->context_date?->translatedFormat('j M Y'),
            'summary' => (string) $row->summary,
            'payload' => $row->payload,
            'error_message' => (string) ($row->error_message ?: ''),
        ];

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->selectedId = null;
        $this->selected = [];
    }

    public function render()
    {
        $from = Carbon::parse($this->from)->toDateString();
        $to = Carbon::parse($this->to)->toDateString();

        $base = NotificationLog::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if ($this->search !== '') {
            $s = trim($this->search);
            $base->where(function ($q) use ($s) {
                $q->where('summary', 'ilike', '%'.$s.'%')
                    ->orWhere('error_message', 'ilike', '%'.$s.'%');
            });
        }

        if ($this->status !== '') {
            $base->where('status', $this->status);
        }

        if ($this->channel !== '') {
            $base->where('channel', $this->channel);
        }

        if ($this->type !== '') {
            $base->where('type', $this->type);
        }

        $rows = (clone $base)->orderByDesc('created_at')->paginate(10);

        $channelOptions = NotificationLog::query()
            ->select('channel')
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        $typeOptions = NotificationLog::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        $items = $rows->map(function (NotificationLog $n) {
            return [
                'id' => (int) $n->id,
                'time' => $n->created_at ? $n->created_at->translatedFormat('j M Y, H:i') : '-',
                'channel' => (string) $n->channel,
                'type' => (string) $n->type,
                'status' => (string) $n->status,
                'summary' => (string) ($n->summary ?: ''),
            ];
        })->all();

        return view('livewire.admin.notification-history-page', [
            'rows' => $rows,
            'items' => $items,
            'channelOptions' => $channelOptions,
            'typeOptions' => $typeOptions,
        ])->layout('components.layouts.app', [
            'title' => 'Riwayat Notifikasi',
        ]);
    }
}

