<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;

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
        Route::post('/{id}/start-view', [VideoController::class, 'startView']);
        Route::post('/{id}/complete-view', [VideoController::class, 'endView']);
        Route::post('/{id}/cancel-view', [VideoController::class, 'cancelView']);
    });
});

// Admin-only routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'users']);
    Route::get('/{id}/users', [UserController::class, 'getUserById']);
    Route::patch('/{id}/users/toggle-active', [UserController::class, 'toggleUserActiveStatus']);
});
