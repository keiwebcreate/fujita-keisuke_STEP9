<?php

use App\Http\Controllers\ApiPurchaseController;
use Illuminate\Support\Facades\Route;

/**
 * APIは画面ではなくデータを返す窓口です。
 * auth:sanctumでBearerトークンを確認し、abilitiesで購入権限も確認します。
 */
Route::post('/products/{product_id}/purchases', [ApiPurchaseController::class, 'store'])
    ->whereNumber('product_id')->middleware(['auth:sanctum', 'abilities:purchases:create'])
    ->name('api.purchases.store');
