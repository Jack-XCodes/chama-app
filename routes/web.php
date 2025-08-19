<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Dashboard & Main Routes
Route::get('dashboard', \App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Payment Routes
Route::get('payments', \App\Livewire\PaymentHistory::class)
    ->middleware(['auth'])
    ->name('payments');

Route::get('payment-submission', \App\Livewire\PaymentSubmission::class)
    ->middleware(['auth'])
    ->name('payment-submission');

// Document Routes
Route::get('documents', \App\Filament\Resources\DocumentResource\Pages\ListDocuments::class)
    ->middleware(['auth'])
    ->name('documents');

// Treasurer Routes
Route::middleware(['auth', 'can:manage-finances'])->prefix('treasurer')->name('treasurer.')->group(function () {
    Route::get('dashboard', \App\Livewire\Treasurer\Dashboard::class)->name('dashboard');
    Route::get('payments', \App\Livewire\Treasurer\PaymentQueue::class)->name('payments');
    Route::get('manual-transaction', \App\Livewire\Treasurer\ManualTransaction::class)->name('manual-transaction');
});

// Reports Routes
Route::middleware(['auth', 'can:manage-finances'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('generate', \App\Livewire\Reports\ReportGenerator::class)->name('generate');
    Route::get('archive', \App\Livewire\Reports\ReportArchive::class)->name('archive');
});

// Admin Routes
Route::middleware(['auth', 'can:manage-users'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', \App\Filament\Resources\UserResource\Pages\ListUsers::class)->name('dashboard');
    Route::get('announcements', \App\Livewire\Admin\AnnouncementManager::class)->name('announcements');
});

// User Profile Routes
Route::middleware(['auth'])->group(function () {
    Route::view('profile', 'profile')->name('profile');
    Route::get('notification-preferences', \App\Livewire\NotificationPreferences::class)->name('notification-preferences');
});

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
