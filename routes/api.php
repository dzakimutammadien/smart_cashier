<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\ReportExportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // API routes for categories
    Route::apiResource('categories', CategoryController::class);

    // API routes for products
    Route::apiResource('products', ProductController::class);
    Route::get('products/search', [ProductController::class, 'search']);

    // Order routes
    Route::post('/orders', [OrderController::class, 'store']);

    // Recommendation routes
    Route::get('/recommendations/popular', [RecommendationController::class, 'popular']);
    Route::get('/recommendations/customers-also-bought/{productId}', [RecommendationController::class, 'customersAlsoBought']);
    Route::get('/recommendations/personalized', [RecommendationController::class, 'personalized']);
    Route::get('/recommendations', [RecommendationController::class, 'recommendations']);

    // Report routes
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
    Route::get('/reports/weekly-sales', [ReportController::class, 'weeklySales']);
    Route::get('/reports/monthly-sales', [ReportController::class, 'monthlySales']);
    Route::get('/reports/product-analytics', [ReportController::class, 'productAnalytics']);
    Route::get('/reports/revenue-statistics', [RevenueController::class, 'revenueStatistics']);
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/low-stock-products', [ReportController::class, 'lowStockProducts']);

    // Export routes
    Route::get('/reports/export/sales', [ReportController::class, 'exportSalesReport']);
    Route::get('/reports/export/product-analytics', [ReportController::class, 'exportProductAnalytics']);
    Route::get('/reports/export/orders', [ReportController::class, 'exportOrders']);
    Route::get('/reports/export/low-stock-products', [ReportController::class, 'exportLowStockProducts']);
    Route::get('/reports/export-pdf', [ReportExportController::class, 'exportPdf']);
    Route::get('/reports/export-excel', [ReportExportController::class, 'exportExcel']);
});