<?php

use Illuminate\Support\Facades\Route;

// Public "/" is the starter install/demo explainer. The authenticated launcher
// is "/home" (evolayer.base.home), provided by the package marketing-pages
// feature route when the kitchen-sink example set is enabled.
Route::inertia('/', 'evolayer/about')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
