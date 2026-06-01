<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TryoutController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CategoryController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::get('/user/analytics', [TryoutController::class, 'getUserAnalytics']);
    
    Route::get('/tryouts', [TryoutController::class, 'index']);
    Route::get('/tryouts/{id}', [TryoutController::class, 'show']);
    Route::post('/tryouts/{id}/review', [TryoutController::class, 'submitReview']);
    Route::get('/tryouts/{id}/leaderboard', [TryoutController::class, 'getLeaderboard']);
    Route::post('/tryouts/{id}/submit', [TryoutController::class, 'submit']);
    
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/bundles', [\App\Http\Controllers\BundleController::class, 'index']);
    Route::get('/bundles/{id}', [\App\Http\Controllers\BundleController::class, 'show']);
    Route::get('/results/{resultId}', [TryoutController::class, 'getResult']);
    Route::get('/results/{resultId}/review', [TryoutController::class, 'getReview']);

    // Orders & Voucher
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/my', [OrderController::class, 'myOrders']);
    Route::post('/voucher/validate', [OrderController::class, 'validateVoucher']);
    Route::get('/vouchers/available', [OrderController::class, 'getAvailableVouchers']);

    // Admin Routes
    Route::middleware(['is_admin'])->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        Route::get('/subcategories', [AdminController::class, 'getSubCategories']);
        Route::post('/subcategories', [AdminController::class, 'createSubCategory']);
        Route::put('/subcategories/{id}', [AdminController::class, 'updateSubCategory']);
        Route::delete('/subcategories/{id}', [AdminController::class, 'deleteSubCategory']);

        Route::get('/reviews', [AdminController::class, 'getReviews']);
        Route::delete('/reviews/{id}', [AdminController::class, 'deleteReview']);

        Route::get('/tryouts', [AdminController::class, 'getTryouts']);
        Route::post('/tryouts', [AdminController::class, 'createTryout']);
        Route::post('/tryouts/{id}', [AdminController::class, 'updateTryout']);
        Route::delete('/tryouts/{id}', [AdminController::class, 'deleteTryout']);

        Route::get('/tryouts/{tryoutId}/questions', [AdminController::class, 'getQuestions']);
        Route::post('/tryouts/{tryoutId}/questions', [AdminController::class, 'createQuestion']);
        Route::put('/tryouts/{tryoutId}/questions/{questionId}', [AdminController::class, 'updateQuestion']);
        Route::delete('/tryouts/{tryoutId}/questions/{questionId}', [AdminController::class, 'deleteQuestion']);

        Route::get('/bundles', [AdminController::class, 'getBundles']);
        Route::post('/bundles', [AdminController::class, 'createBundle']);
        Route::post('/bundles/{id}', [AdminController::class, 'updateBundle']);
        Route::delete('/bundles/{id}', [AdminController::class, 'deleteBundle']);

        // Admin: Orders
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::put('/orders/{id}/status', [OrderController::class, 'adminUpdateStatus']);

        // Admin: Vouchers
        Route::get('/vouchers', [OrderController::class, 'getVouchers']);
        Route::post('/vouchers', [OrderController::class, 'createVoucher']);
        Route::put('/vouchers/{id}', [OrderController::class, 'updateVoucher']);
        Route::delete('/vouchers/{id}', [OrderController::class, 'deleteVoucher']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    });
});
