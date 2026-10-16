<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FrenchDoorController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\FrenchDoorOptionsController;
use App\Models\ProductOption;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('admin.login.store');

Route::get('/api/products/french-door/options', FrenchDoorOptionsController::class)
    ->name('api.products.french-door.options');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::get('/products/french-door', [FrenchDoorController::class, 'index'])->name('products.french-door.index');
    Route::post('/products/french-door/options', [FrenchDoorController::class, 'store'])->name('products.french-door.options.store');
    Route::put('/products/french-door/options/{option}', [FrenchDoorController::class, 'update'])->name('products.french-door.options.update');
    Route::delete('/products/french-door/options/{option}', [FrenchDoorController::class, 'destroy'])->name('products.french-door.options.destroy');
    Route::post('/users/{user}/roles', [UserRoleController::class, 'update'])->name('users.roles.update');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
