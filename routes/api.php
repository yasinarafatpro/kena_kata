<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Public catalogue
Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product:slug}', [ProductController::class, 'show']);
Route::get('payment-methods', [PaymentController::class, 'methods']);

// Provider callbacks: no auth, authenticity is verified by signature inside each gateway
Route::post('webhooks/{provider}', WebhookController::class)->middleware('throttle:120,1');

// Customer
Route::middleware('auth:sanctum')->group(function () {
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::post('cart/merge', [CartController::class, 'merge']);
    Route::patch('cart/{id}', [CartController::class, 'update']);
    Route::delete('cart/{id}', [CartController::class, 'destroy']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('checkout', [PaymentController::class, 'checkout'])->middleware('throttle:10,1');
});

// Admin
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
    Route::get('products', [ProductController::class, 'adminIndex']);
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
    Route::post('categories', [CategoryController::class, 'store']);

    Route::get('orders', [OrderController::class, 'adminIndex']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
});
