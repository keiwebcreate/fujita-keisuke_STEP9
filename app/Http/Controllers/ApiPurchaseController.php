<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;

class ApiPurchaseController extends Controller
{
    public function store(PurchaseRequest $request, string $product_id): JsonResponse
    {
        $sale = Sale::purchase($request->user()->id, $product_id, (int) $request->validated('quantity'));

        return (new PurchaseResource($sale))->response()->setStatusCode(201);
    }
}
