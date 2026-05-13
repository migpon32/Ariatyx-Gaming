<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FirebaseTokenController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::get('/leaderboard', function () {
    return redirect()->away('http://your-python-app-url.com');
})->name('leaderboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/game-play', function () {
    return response()
        ->view('game_play')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0')
        ->header('Surrogate-Control', 'no-store');
})->name('game.play');

Route::middleware('auth')->group(function () {
    Route::post('/firebase/token', [FirebaseTokenController::class, 'store'])->name('firebase.token.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/launcher', function () {
        return view('launcher');
    })->name('launcher');
});

require __DIR__.'/auth.php';
