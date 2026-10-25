<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ListedProductController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request): RedirectResponse => to_route(
    $request->user() ? 'products.index' : 'login'
))->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
    Route::get('/account/edit', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
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
    Route::get('/products/{product_id}/purchase', [PurchaseController::class, 'create'])
        ->whereNumber('product_id')->name('purchases.create');
    Route::post('/products/{product_id}/purchases', [PurchaseController::class, 'store'])
        ->whereNumber('product_id')->name('purchases.store');
    Route::get('/products/{product_id}', [ProductController::class, 'show'])
        ->whereNumber('product_id')->name('products.show');
});

require __DIR__.'/auth.php';
