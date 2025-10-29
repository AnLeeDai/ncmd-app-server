<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\SpinController;

// Public routes
Route::prefix('public')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::prefix('videos')->group(function () {
        Route::get('/', [VideoController::class, 'videos']);
        Route::get('/{id}', [VideoController::class, 'getVideoById']);
    });
});

// Private routes
Route::prefix('private')->middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('videos')->group(function () {
        Route::post('/start-view', [VideoController::class, 'startView']);
        Route::post('/complete-view', [VideoController::class, 'endView']);
        Route::post('/cancel-view', [VideoController::class, 'cancelView']);
    });

    Route::prefix('points')->group(function () {
        Route::get('/history', [PointController::class, 'pointHistoryUser']);
    });

    Route::prefix('spin')->group(function () {
        Route::post('/', [SpinController::class, 'spin']);
        Route::get('/rewards', [SpinController::class, 'allRewards']);
    });
});

// Admin-only routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'users']);
        Route::get('/{id}', [UserController::class, 'getUserById']);
        Route::patch('/{id}/toggle-active', [UserController::class, 'toggleUserActiveStatus']);
    });

    Route::prefix('points')->group(function () {
        Route::get('/{id}/history', [PointController::class, 'pointHistoryById']);
    });

    Route::prefix('spin')->group(function () {
        Route::get('/addReward', [SpinController::class, 'addReward']);
    });
});
