<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php
Route::get('/leaderboard', function () {
    // Redirect to the Python server
    return redirect()->away('http://your-python-app-url.com');
})->name('leaderboard');

// This is the route for your launcher page
Route::get('/launcher', function () {
    return view('launcher');
})->name('launcher');

// This is the MISSING route that caused your error
Route::get('/game-play', function () {
    return view('game_play'); // Create a file named game_play.blade.php
})->name('game.play');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
