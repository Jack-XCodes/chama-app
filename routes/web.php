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
    // Add finance management routes here
});

require __DIR__.'/auth.php';
