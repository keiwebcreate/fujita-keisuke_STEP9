<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request): RedirectResponse => to_route(
    $request->user() ? 'products.index' : 'login'
))->name('home');

Route::view('/products', 'products.index')->middleware('auth')->name('products.index');

require __DIR__.'/auth.php';
