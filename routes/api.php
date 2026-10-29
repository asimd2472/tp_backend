<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\ProductCatalogController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductDetailsController;
use App\Http\Controllers\Api\WindowCatalogController;
use App\Http\Controllers\FrenchDoorOptionsController;
use Illuminate\Support\Facades\Route;

Route::get('/blogs', [BlogController::class, 'index'])
    ->name('api.blogs.index');
Route::get('/blog-details/{slug}', [BlogController::class, 'details'])
    ->name('api.blogs.details');

Route::get('/products', [ProductCatalogController::class, 'index'])
    ->name('api.products.index');
Route::get('/products/{slug}/models', [ProductCatalogController::class, 'modelsByProduct'])
    ->name('api.products.models');
Route::get('/products/french-door/options', FrenchDoorOptionsController::class)
    ->name('api.products.french-door.options');
Route::get('/products/{slug}', [ProductCatalogController::class, 'show'])
    ->name('api.products.show');
Route::get('/product-details', ProductDetailsController::class)
    ->name('api.product-details');
Route::get('/product-details/{model_id}', ProductDetailsController::class)
    ->name('api.product-details-by-id');
Route::get('/product/french-door', [ProductController::class, 'french_door'])
    ->name('api.product.french-door');

Route::get('/windows', [WindowCatalogController::class, 'index'])
    ->name('api.windows.index');
Route::get('/windows/{slug}/models', [WindowCatalogController::class, 'modelsByWindow'])
    ->name('api.windows.models');
Route::get('/windows/{slug}', [WindowCatalogController::class, 'show'])
    ->name('api.windows.show');
Route::get('/window-details', [WindowCatalogController::class, 'windowDetails'])
    ->name('api.window-details');
Route::get('/window-details/{model_id}', [WindowCatalogController::class, 'windowDetails'])
    ->name('api.window-details-by-id');



Route::post('/register', [AuthController::class, 'register'])
    ->middleware(['frontend.origin', 'throttle:5,1'])
    ->name('api.auth.register');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['frontend.origin', 'throttle:5,1'])
    ->name('api.auth.login');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
    ->middleware(['frontend.origin', 'throttle:10,1'])
    ->name('api.auth.verify-otp');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])
    ->middleware(['frontend.origin', 'throttle:5,1'])
    ->name('api.auth.resend-otp');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
});
