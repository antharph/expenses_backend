<?php

use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);
    Route::post('/auth/google', GoogleAuthController::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/dashboard', DashboardController::class);
        Route::post('/logout', LogoutController::class);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expenses', [ExpenseController::class, 'store']);
    });
});
