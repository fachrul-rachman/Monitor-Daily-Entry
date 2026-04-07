<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| STATIC PREVIEW ROUTES — Untuk review UI tanpa auth
| TODO: Hapus semua route ini setelah backend Livewire siap
|--------------------------------------------------------------------------
*/

// Landing — Role Switcher (preview)
Route::view('/', 'preview.role-switcher')->name('home');

// Dashboard hub — redirect based on authenticated user's role
Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    return match ($user->role) {
        'admin' => redirect()->route('admin.home'),
        'director' => redirect()->route('director.dashboard'),
        'hod' => redirect()->route('hod.dashboard'),
        'manager' => redirect()->route('manager.dashboard'),
        default => abort(403),
    };
})->name('dashboard');

// ============ Admin Routes ============
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::view('/', 'livewire.admin.home-page')->name('home');
        Route::get('/users', \App\Livewire\Admin\UsersPage::class)->name('users');
        Route::get('/divisions', \App\Livewire\Admin\DivisionsPage::class)->name('divisions');
        Route::view('/assignments', 'livewire.admin.assignments-page')->name('assignments');
        Route::get('/settings', \App\Livewire\Admin\ReportSettingsPage::class)->name('settings');
        Route::view('/leave', 'livewire.admin.leave-page')->name('leave');
        Route::view('/override', 'livewire.admin.override-page')->name('override');
        Route::view('/notifications', 'livewire.admin.notification-history-page')->name('notifications');
    });

// ============ Director Routes ============
Route::prefix('director')
    ->name('director.')
    ->middleware(['auth', 'role:director'])
    ->group(function () {
        Route::view('/dashboard', 'livewire.director.dashboard-page')->name('dashboard');
        Route::view('/company', 'livewire.director.company-page')->name('company');
        Route::view('/divisions', 'livewire.director.divisions-page')->name('divisions');
        Route::view('/ai-chat', 'livewire.director.ai-chat-page')->name('ai-chat');
    });

// ============ HoD Routes ============
Route::prefix('hod')
    ->name('hod.')
    ->middleware(['auth', 'role:hod'])
    ->group(function () {
        Route::view('/dashboard', 'livewire.hod.dashboard-page')->name('dashboard');
        Route::get('/daily-entry', \App\Livewire\Hod\DailyEntryPage::class)->name('daily-entry');
        Route::view('/history', 'livewire.hod.history-page')->name('history');
        Route::view('/big-rock', 'livewire.hod.big-rock-page')->name('big-rock');
        Route::view('/division-entries', 'livewire.hod.division-entries-page')->name('division-entries');
        Route::view('/division-summary', 'livewire.hod.division-summary-page')->name('division-summary');
        Route::view('/ai-chat', 'livewire.hod.ai-chat-page')->name('ai-chat');
    });

// ============ Manager Routes ============
Route::prefix('manager')
    ->name('manager.')
    ->middleware(['auth', 'role:manager'])
    ->group(function () {
        Route::view('/dashboard', 'livewire.manager.dashboard-page')->name('dashboard');
        Route::get('/daily-entry', \App\Livewire\Manager\DailyEntryPage::class)->name('daily-entry');
        Route::view('/history', 'livewire.manager.history-page')->name('history');
        Route::view('/big-rock', 'livewire.manager.big-rock-page')->name('big-rock');
    });

require __DIR__.'/settings.php';
