<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PhoneAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::redirect('/login', '/app/login')->name('login');

Route::get('/auth/phone', [PhoneAuthController::class, 'show'])->name('phone.login');
Route::post('/auth/otp', [PhoneAuthController::class, 'send'])->middleware('throttle:3,1');
Route::post('/auth/verify', [PhoneAuthController::class, 'verify'])->middleware('throttle:10,1');
Route::middleware('auth')->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show']);
    Route::post('/onboarding', [OnboardingController::class, 'store'])->middleware('throttle:5,1');
    Route::post('/logout', [PhoneAuthController::class, 'logout']);
});
