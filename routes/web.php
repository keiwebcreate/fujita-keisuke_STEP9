<?php

use App\Http\Controllers\LikeController;
use App\Http\Controllers\ListedProductController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request): RedirectResponse => to_route(
    $request->user() ? 'products.index' : 'login'
))->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/mypage', [MyPageController::class, 'show'])->name('mypage.show');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ListedProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ListedProductController::class, 'store'])->name('products.store');
    Route::get('/mypage/products/{product_id}', [ListedProductController::class, 'show'])
        ->whereNumber('product_id')->name('listed_products.show');
    Route::get('/mypage/products/{product_id}/edit', [ListedProductController::class, 'edit'])
        ->whereNumber('product_id')->name('listed_products.edit');
    Route::patch('/mypage/products/{product_id}', [ListedProductController::class, 'update'])
        ->whereNumber('product_id')->name('listed_products.update');
    Route::delete('/mypage/products/{product_id}', [ListedProductController::class, 'destroy'])
        ->whereNumber('product_id')->name('listed_products.destroy');
    Route::put('/products/{product_id}/like', [LikeController::class, 'store'])
        ->whereNumber('product_id')->name('products.like.store');
    Route::delete('/products/{product_id}/like', [LikeController::class, 'destroy'])
        ->whereNumber('product_id')->name('products.like.destroy');
    Route::get('/products/{product_id}', [ProductController::class, 'show'])
        ->whereNumber('product_id')->name('products.show');
});

require __DIR__.'/auth.php';
