<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GameController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('/login/two-factor', [AuthController::class, 'verifyTwoFactorLogin'])
        ->middleware('throttle:8,1');

    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    Route::get('/games/{game}/leaderboard', [LeaderboardController::class, 'index']);
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/two-factor/setup', [TwoFactorController::class, 'setup']);
        Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])
            ->middleware('throttle:8,1');
        Route::get('/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
        Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
            ->middleware('throttle:5,1');
        Route::delete('/two-factor', [TwoFactorController::class, 'disable'])
            ->middleware('throttle:5,1');

        Route::middleware('api.2fa.complete')->group(function () {
            Route::get('/games/{game}/me', [GameController::class, 'me']);
            Route::post('/games/{game}/sessions', [GameController::class, 'startSession'])
                ->middleware('throttle:30,1');
            Route::patch('/games/{game}/sessions/{session}', [GameController::class, 'endSession'])
                ->middleware('throttle:30,1');
            Route::post('/games/{game}/scores', [GameController::class, 'submitScore'])
                ->middleware('throttle:20,1');
        });
    });
});
