<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecommendationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // API routes for products
    Route::apiResource('products', ProductController::class);

    // Order routes
    Route::post('/orders', [OrderController::class, 'store']);

    // Recommendation routes
    Route::get('/recommendations/popular', [RecommendationController::class, 'popular']);
    Route::get('/recommendations/customers-also-bought/{productId}', [RecommendationController::class, 'customersAlsoBought']);
    Route::get('/recommendations/personalized', [RecommendationController::class, 'personalized']);
    Route::get('/recommendations', [RecommendationController::class, 'recommendations']);
});