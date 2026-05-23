<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorDisableController;
use App\Http\Controllers\Auth\TwoFactorSetupController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('guest')->group(function () {
    Route::get('two-factor/challenge', [TwoFactorChallengeController::class, 'show'])
        ->name('two-factor.challenge');

    Route::post('two-factor/challenge', [TwoFactorChallengeController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('two-factor.verify');
});

Route::middleware('auth')->group(function () {
    Route::get('two-factor/setup', [TwoFactorSetupController::class, 'show'])
        ->name('two-factor.setup');

    Route::post('two-factor/setup', [TwoFactorSetupController::class, 'confirm'])
        ->middleware('throttle:10,1')
        ->name('two-factor.confirm');

    Route::get('two-factor/recovery-codes', [TwoFactorSetupController::class, 'recoveryCodes'])
        ->middleware(['2fa.enabled', '2fa.verified'])
        ->name('two-factor.recovery-codes');

    Route::post('two-factor/recovery-codes', [TwoFactorSetupController::class, 'regenerateRecoveryCodes'])
        ->middleware(['2fa.enabled', '2fa.verified', 'password.confirm'])
        ->name('two-factor.recovery-codes.regenerate');

    Route::get('two-factor/disable', [TwoFactorDisableController::class, 'show'])
        ->middleware(['2fa.enabled', '2fa.verified', 'password.confirm'])
        ->name('two-factor.disable');

    Route::delete('two-factor/disable', [TwoFactorDisableController::class, 'destroy'])
        ->middleware(['2fa.enabled', '2fa.verified', 'password.confirm'])
        ->name('two-factor.destroy');

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
