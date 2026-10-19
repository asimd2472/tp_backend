<?php

use App\Http\Controllers\Api\ProductCatalogController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\FrenchDoorOptionsController;
use Illuminate\Support\Facades\Route;

Route::get('/products/french-door/options', FrenchDoorOptionsController::class)
    ->name('api.products.french-door.options');
Route::get('/products/{slug}', [ProductCatalogController::class, 'show'])
    ->name('api.products.show');
Route::get('/product/french-door', [ProductController::class, 'french_door'])
    ->name('api.product.french-door');
