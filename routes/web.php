<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

// routes/web.php
Route::get('/leaderboard', function () {
    // Redirect to the Python server
    return redirect()->away('http://your-python-app-url.com');
})->name('leaderboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/game-play', function () {
    return view('game_play');
})->middleware(['auth', 'verified'])->name('game.play');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // This is the route for your launcher page
    Route::get('/launcher', function () {
        return view('launcher');
    })->name('launcher');
});

require __DIR__.'/auth.php';
