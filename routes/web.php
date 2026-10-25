<?php

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
    Route::get('/products/{product_id}', [ProductController::class, 'show'])
        ->whereNumber('product_id')->name('products.show');
});

require __DIR__.'/auth.php';
