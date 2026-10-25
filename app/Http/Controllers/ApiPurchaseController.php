<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;

class ApiPurchaseController extends Controller
{
    /**
     * 画面の購入と同じモデル処理を呼び、結果をJSON（データ交換用の形式）で返します。
     * 201は「新しい購入記録を作れた」というHTTPの結果番号です。
     */
    public function store(PurchaseRequest $request, string $product_id): JsonResponse
    {
        $sale = Sale::purchase($request->user()->id, $product_id, (int) $request->validated('quantity'));

        return (new PurchaseResource($sale))->response()->setStatusCode(201);
    }
}
