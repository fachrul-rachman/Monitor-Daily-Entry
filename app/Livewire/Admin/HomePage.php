<?php

namespace App\Livewire\Admin;

use App\Models\Finding;
use App\Models\LeaveRequest;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Home')]
class HomePage extends Component
{
    public function render()
    {
        $today = Carbon::today();
        $todayKey = $today->toDateString();

        $exceptionsToday = Finding::query()
            ->whereDate('finding_date', $todayKey)
            ->whereIn('severity', ['medium', 'high'])
            ->count();

        $pendingLeaveCount = LeaveRequest::query()
            ->where('status', 'pending')
            ->count();

        $failedNotificationsCount = NotificationLog::query()
            ->where('status', 'failed')
            ->whereDate('created_at', '>=', $today->copy()->subDays(7)->toDateString())
            ->count();

        $activeUsersCount = User::query()
            ->where('status', 'active')
            ->count();

        $pendingLeaveList = LeaveRequest::query()
            ->with(['user:id,name,division_id', 'division:id,name'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'user_id', 'division_id', 'type', 'start_date', 'end_date', 'status'])
            ->map(function (LeaveRequest $r) {
                $range = Carbon::parse($r->start_date)->translatedFormat('j M Y');
                $end = Carbon::parse($r->end_date)->translatedFormat('j M Y');
                $date = $range === $end ? $range : ($range.' - '.$end);

                return [
                    'id' => (int) $r->id,
                    'name' => (string) ($r->user?->name ?: '-'),
                    'division' => (string) ($r->division?->name ?: '-'),
                    'type' => (string) $r->type,
                    'date' => $date,
                    'status' => (string) $r->status,
                ];
            })
            ->all();

        $failedNotifications = NotificationLog::query()
            ->where('status', 'failed')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'channel', 'error_message', 'created_at'])
            ->map(function (NotificationLog $n) {
                return [
                    'id' => (int) $n->id,
                    'time' => $n->created_at ? $n->created_at->format('H:i') : '-',
                    'channel' => (string) $n->channel,
                    'error' => (string) ($n->error_message ?: '-'),
                ];
            })
            ->all();

        return view('livewire.admin.home-page', [
            'todayDate' => $today->translatedFormat('l, j F Y'),
            'hasExceptions' => $exceptionsToday > 0,
            'hasPendingLeave' => $pendingLeaveCount > 0,
            'summaryCards' => [
                'exceptions_today' => $exceptionsToday,
                'pending_leave' => $pendingLeaveCount,
                'failed_notifications' => $failedNotificationsCount,
                'active_users' => $activeUsersCount,
            ],
            'pendingLeaveList' => $pendingLeaveList,
            'failedNotifications' => $failedNotifications,
        ])->layout('components.layouts.app', [
            'title' => 'Home',
        ]);
    }
}

