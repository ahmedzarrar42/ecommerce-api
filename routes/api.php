<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {

    // Auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Products (public browse)
    Route::get('/products',         [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Categories (public browse)
    Route::get('/categories',             [CategoryController::class, 'index']);
    Route::get('/categories/{category}',  [CategoryController::class, 'show']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // Cart
        Route::get('/cart',                     [CartController::class, 'index']);
        Route::post('/cart/items',              [CartController::class, 'addItem']);
        Route::put('/cart/items/{cartItem}',    [CartController::class, 'updateItem']);
        Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem']);
        Route::delete('/cart',                  [CartController::class, 'clear']);

        // Orders
        Route::get('/orders',               [OrderController::class, 'index']);
        Route::post('/orders',              [OrderController::class, 'store']);
        Route::get('/orders/{order}',       [OrderController::class, 'show']);
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

        // Admin only routes
        Route::middleware('can:admin')->group(function () {
            Route::post('/products',            [ProductController::class, 'store']);
            Route::put('/products/{product}',   [ProductController::class, 'update']);
            Route::delete('/products/{product}',[ProductController::class, 'destroy']);

            Route::post('/categories',              [CategoryController::class, 'store']);
            Route::put('/categories/{category}',    [CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
        });
    });
});
