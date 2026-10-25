<?php

use App\Http\Controllers\ApiPurchaseController;
use Illuminate\Support\Facades\Route;

Route::post('/products/{product_id}/purchases', [ApiPurchaseController::class, 'store'])
    ->whereNumber('product_id')->middleware(['auth:sanctum', 'abilities:purchases:create'])
    ->name('api.purchases.store');
