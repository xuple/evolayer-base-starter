<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public "/" uses the package explainer while that managed surface is enabled,
// then falls back to the host-owned Laravel welcome page when the application
// profile removes package examples.
Route::inertia(
    '/',
    config('evolayer.base.examples.marketing_pages') ? 'evolayer/base' : 'welcome',
)->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    // Greeting hour is computed server-side and passed as a prop so the SSR
    // render and client hydration agree (no Date()-in-render mismatch).
    Route::get('home', fn () => Inertia::render('home', [
        'greetingHour' => now()->hour,
    ]))->defaults('component', 'home')->name('home');

    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
