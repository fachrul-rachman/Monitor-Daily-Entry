<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| STATIC PREVIEW ROUTES — Untuk review UI tanpa auth
| TODO: Hapus semua route ini setelah backend Livewire siap
|--------------------------------------------------------------------------
*/

// Landing page
Route::view('/', 'welcome')->name('home');

// Dashboard hub — redirect based on authenticated user's role
Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    return match ($user->role) {
        'admin' => redirect()->route('admin.home'),
        'director' => redirect()->route('director.dashboard'),
        'hod' => redirect()->route('hod.dashboard'),
        'manager' => redirect()->route('manager.dashboard'),
        'iso' => redirect()->route('iso.monitor'),
        default => abort(403),
    };
})->name('dashboard');

// ============ Admin Routes ============
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/', \App\Livewire\Admin\HomePage::class)->name('home');
        Route::get('/users', \App\Livewire\Admin\UsersPage::class)->name('users');
        Route::get('/divisions', \App\Livewire\Admin\DivisionsPage::class)->name('divisions');
        Route::get('/assignments', \App\Livewire\Admin\AssignmentsPage::class)->name('assignments');
        Route::get('/settings', \App\Livewire\Admin\ReportSettingsPage::class)->name('settings');
        Route::get('/security', \App\Livewire\Shared\ChangePasswordPage::class)->name('security');
        Route::get('/leave', \App\Livewire\Admin\LeavePage::class)->name('leave');
        Route::get('/override', \App\Livewire\Admin\OverridePage::class)->name('override');
        Route::get('/notifications', \App\Livewire\Admin\NotificationHistoryPage::class)->name('notifications');
    });

// ============ Director Routes ============
Route::prefix('director')
    ->name('director.')
    ->middleware(['auth', 'role:director'])
    ->group(function () {
        Route::view('/dashboard', 'livewire.director.dashboard-page')->name('dashboard');
        Route::view('/company', 'livewire.director.company-page')->name('company');
        Route::view('/divisions', 'livewire.director.divisions-page')->name('divisions');
        Route::get('/security', \App\Livewire\Shared\ChangePasswordPage::class)->name('security');
        Route::get('/ai-chat', \App\Livewire\Director\AiChatPage::class)->name('ai-chat');

        // Kelola (akses administrasi terbatas untuk Director)
        Route::prefix('manage')
            ->name('manage.')
            ->group(function () {
                Route::get('/users', \App\Livewire\Admin\UsersPage::class)->name('users');
                Route::get('/divisions', \App\Livewire\Admin\DivisionsPage::class)->name('divisions');
                Route::get('/assignments', \App\Livewire\Admin\AssignmentsPage::class)->name('assignments');
                Route::get('/leave', \App\Livewire\Admin\LeavePage::class)->name('leave');
            });
    });

// ============ HoD Routes ============
Route::prefix('hod')
    ->name('hod.')
    ->middleware(['auth', 'role:hod'])
    ->group(function () {
        Route::view('/dashboard', 'livewire.hod.dashboard-page')->name('dashboard');
        Route::get('/daily-entry', \App\Livewire\Hod\DailyEntryPage::class)->name('daily-entry');
        Route::get('/history', \App\Livewire\Hod\HistoryPage::class)->name('history');
        Route::get('/big-rock', \App\Livewire\Shared\BigRockPage::class)->name('big-rock');
        Route::get('/security', \App\Livewire\Shared\ChangePasswordPage::class)->name('security');
        Route::get('/leave', \App\Livewire\Hod\LeaveRequestPage::class)->name('leave');
        Route::get('/division-entries', \App\Livewire\Hod\DivisionEntriesPage::class)->name('division-entries');
        Route::get('/team-big-rock', \App\Livewire\Hod\TeamBigRockPage::class)->name('team-big-rock');
        Route::view('/division-summary', 'livewire.hod.division-summary-page')->name('division-summary');
        Route::get('/ai-chat', \App\Livewire\Hod\AiChatPage::class)->name('ai-chat');
    });

// ============ Manager Routes ============
Route::prefix('manager')
    ->name('manager.')
    ->middleware(['auth', 'role:manager'])
    ->group(function () {
        Route::view('/dashboard', 'livewire.manager.dashboard-page')->name('dashboard');
        Route::get('/daily-entry', \App\Livewire\Manager\DailyEntryPage::class)->name('daily-entry');
        Route::get('/history', \App\Livewire\Manager\HistoryPage::class)->name('history');
        Route::get('/big-rock', \App\Livewire\Shared\BigRockPage::class)->name('big-rock');
        Route::get('/security', \App\Livewire\Shared\ChangePasswordPage::class)->name('security');
        Route::get('/leave', \App\Livewire\Manager\LeaveRequestPage::class)->name('leave');
    });

// ============ ISO Routes ============
Route::prefix('iso')
    ->name('iso.')
    ->middleware(['auth', 'role:iso'])
    ->group(function () {
        Route::get('/monitor', \App\Livewire\Iso\MonitorPage::class)->name('monitor');
        Route::get('/leave', \App\Livewire\Iso\LeaveApprovalPage::class)->name('leave');
        Route::get('/security', \App\Livewire\Shared\ChangePasswordPage::class)->name('security');
    });

require __DIR__.'/settings.php';
