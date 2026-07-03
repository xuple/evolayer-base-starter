<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public "/" is the starter install/demo explainer (route `welcome`, component
// `evolayer/base`). The authenticated launcher "/home" (route `home`) is
// host-owned here — the package no longer registers an auth landing route.
Route::inertia('/', 'evolayer/base')->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    // Greeting hour is computed server-side and passed as a prop so the SSR
    // render and client hydration agree (no Date()-in-render mismatch).
    Route::get('home', fn () => Inertia::render('home', [
        'greetingHour' => now()->hour,
    ]))->defaults('component', 'home')->name('home');

    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
