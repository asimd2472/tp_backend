<?php

use App\Http\Controllers\Api\ProductCatalogController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WindowCatalogController;
use App\Http\Controllers\FrenchDoorOptionsController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductCatalogController::class, 'index'])
    ->name('api.products.index');
Route::get('/products/{slug}/models', [ProductCatalogController::class, 'modelsByProduct'])
    ->name('api.products.models');
Route::get('/products/french-door/options', FrenchDoorOptionsController::class)
    ->name('api.products.french-door.options');
Route::get('/products/{slug}', [ProductCatalogController::class, 'show'])
    ->name('api.products.show');
Route::get('/product-details', [ProductCatalogController::class, 'productDetails'])
    ->name('api.product-details');
Route::get('/product-details/{model_id}', [ProductCatalogController::class, 'productDetails'])
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
