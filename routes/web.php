<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Example protected management routes
Route::middleware(['auth', 'permission:manage-users'])->group(function () {
    // Add user management routes here
});

Route::middleware(['auth', 'permission:manage-documents'])->group(function () {
    // Add document management routes here
});

Route::middleware(['auth', 'permission:manage-finances'])->group(function () {
    // Finance management routes
    Route::get('/treasurer/dashboard', \App\Livewire\Treasurer\Dashboard::class)->name('treasurer.dashboard');
    Route::get('/treasurer/payments', \App\Livewire\Treasurer\PaymentQueue::class)->name('treasurer.payments');
    Route::get('/treasurer/verification', \App\Livewire\Treasurer\VerificationQueue::class)->name('treasurer.verification');
    Route::get('/treasurer/manual-transaction', \App\Livewire\Treasurer\ManualTransaction::class)->name('treasurer.manual-transaction');
    
    // Report management routes
    Route::get('/reports/generate', \App\Livewire\Reports\ReportGenerator::class)->name('reports.generate');
    Route::get('/reports/archive', \App\Livewire\Reports\ReportArchive::class)->name('reports.archive');
    Route::get('/reports/download/{report}', [App\Http\Controllers\ReportController::class, 'download'])->name('reports.download');
});

// Notification routes
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', \App\Livewire\NotificationCenter::class)->name('notifications.index');
    Route::get('/notification-preferences', \App\Livewire\NotificationPreferences::class)->name('notification-preferences');
});

// Admin routes
Route::middleware(['auth', 'permission:manage-users'])->group(function () {
    Route::get('/admin/announcements', \App\Livewire\Admin\AnnouncementManager::class)->name('admin.announcements');
});

require __DIR__.'/auth.php';
