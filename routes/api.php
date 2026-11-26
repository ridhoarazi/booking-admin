<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FieldController;
use App\Http\Controllers\Api\V1\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'me']);

        Route::apiResource('bookings', BookingController::class)->only(['index', 'show']);
        Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
    });

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('fields', [FieldController::class, 'index']);
    Route::get('fields/{field}', [FieldController::class, 'show']);
    Route::get('fields/{field}/schedules', [ScheduleController::class, 'index']);
});
