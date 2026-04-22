<?php

namespace App\Jobs;

use App\Jobs\ComputeUserMetrics;
use App\Models\DailyEntry;
use App\Models\ReportSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ComputeAllPendingUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $setting = ReportSetting::current();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i');

        $planClosed = $now >= $setting->plan_close_time;
        $realizationClosed = $now >= $setting->realization_close_time;

        if (! $planClosed && ! $realizationClosed) {
            return;
        }

        $users = User::whereIn('role', ['hod', 'manager'])->where('status', 'active')->get();

        foreach ($users as $user) {
            $entry = DailyEntry::where('user_id', $user->id)
                ->whereDate('entry_date', $today)
                ->first();

            if ($planClosed && (! $entry || ! $entry->plan_submitted_at)) {
                ComputeUserMetrics::dispatch($user);
                continue;
            }

            if ($realizationClosed && (! $entry || ! $entry->realization_submitted_at)) {
                ComputeUserMetrics::dispatch($user);
            }
        }
    }
}
