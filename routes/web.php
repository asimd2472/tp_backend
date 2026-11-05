<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\DealerImportController;
use App\Http\Controllers\Admin\FrenchDoorController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Admin\WindowController;
use App\Http\Controllers\Admin\WindowImportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('admin.login.store');
Route::get('/admin/two-factor/setup', [AdminAuthController::class, 'setup'])
    ->name('admin.two-factor.setup');
Route::post('/admin/two-factor/setup', [AdminAuthController::class, 'confirmSetup'])
    ->middleware('throttle:10,1')
    ->name('admin.two-factor.setup.confirm');
Route::get('/admin/two-factor/challenge', [AdminAuthController::class, 'challenge'])
    ->name('admin.two-factor.challenge');
Route::post('/admin/two-factor/challenge', [AdminAuthController::class, 'verifyChallenge'])
    ->middleware('throttle:10,1')
    ->name('admin.two-factor.challenge.verify');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->middleware('can:view_dashboard')->name('dashboard');

    Route::middleware('can:manage_roles')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::middleware('can:manage_users')->group(function () {
        Route::post('/users/{user}/roles', [UserRoleController::class, 'update'])->name('users.roles.update');
    });

    Route::middleware('can:manage_dealers')->group(function () {
        Route::get('/dealers/import', [DealerImportController::class, 'index'])->name('dealers.import.index');
        Route::post('/dealers/import', [DealerImportController::class, 'store'])->name('dealers.import.store');
        Route::resource('dealers', DealerController::class)->except(['show']);
    });

    Route::middleware('can:manage_products')->group(function () {
        Route::resource('products', ProductController::class)->except(['show']);
        Route::post('/products/{product}/models/{model}/image', [ProductController::class, 'uploadModelImage'])
            ->name('products.models.image');
        Route::post('/products/{product}/models/{model}/designs/{design}/images', [ProductController::class, 'uploadDesignGalleryImage'])
            ->name('products.designs.images');
        Route::post('/products/{product}/models/{model}/designs/{design}/shades/{shade}/{kind}', [ProductController::class, 'uploadShadeImage'])
            ->whereIn('kind', ['swatch', 'gallery'])
            ->name('products.shades.image');
        Route::get('/products/import', [ProductImportController::class, 'index'])->name('products.import.index');
        Route::post('/products/import', [ProductImportController::class, 'store'])->name('products.import.store');
    });

    Route::middleware('can:manage_windows')->group(function () {
        Route::get('/windows/import', [WindowImportController::class, 'index'])->name('windows.import.index');
        Route::post('/windows/import', [WindowImportController::class, 'store'])->name('windows.import.store');
        Route::post('/windows/{window}/models/{model}/image', [WindowController::class, 'uploadModelImage'])
            ->name('windows.models.image');
        Route::post('/windows/{window}/models/{model}/shades/{shade}/{kind}', [WindowController::class, 'uploadShadeImage'])
            ->whereIn('kind', ['swatch', 'gallery'])
            ->name('windows.shades.image');
        Route::resource('windows', WindowController::class)->except(['show']);
    });

    Route::middleware('can:manage_blog')->group(function () {
        Route::post('/blog/media', [BlogController::class, 'uploadMedia'])->name('blog.media.store');
        Route::resource('blog', BlogController::class)->except(['show']);
    });

    Route::middleware('can:manage_french_door')->group(function () {
        Route::get('/products/french-door', [FrenchDoorController::class, 'index'])->name('products.french-door.index');
        Route::post('/products/french-door/options', [FrenchDoorController::class, 'store'])->name('products.french-door.options.store');
        Route::put('/products/french-door/options/{option}', [FrenchDoorController::class, 'update'])->name('products.french-door.options.update');
        Route::delete('/products/french-door/options/{option}', [FrenchDoorController::class, 'destroy'])->name('products.french-door.options.destroy');
    });

    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
