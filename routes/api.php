<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\FrenchDoorOptionsController;
use Illuminate\Support\Facades\Route;

Route::get('/products/french-door/options', FrenchDoorOptionsController::class)
    ->name('api.products.french-door.options');
Route::get('/product/french-door', [ProductController::class, 'french_door'])
    ->name('api.product.french-door');
