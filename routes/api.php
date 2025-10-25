<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

// Public routes
Route::prefix('public')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Private routes
Route::prefix('private')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [UserController::class, 'profile']);
});

// Admin-only routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'users']);
    Route::get('/{id}/users', [UserController::class, 'getUserById']);
    Route::patch('/{id}/users/toggle-active', [UserController::class, 'toggleUserActiveStatus']);
});
