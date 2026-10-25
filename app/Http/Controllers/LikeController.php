<?php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * ログインしている人のお気に入りを登録し、結果をJavaScriptへ返します。
     */
    public function store(Request $request, string $product_id): JsonResponse
    {
        return response()->json(['liked' => Like::setForProduct($request->user()->id, $product_id, true)]);
    }

    /**
     * 本人のお気に入りだけ解除し、解除後の状態を返します。
     */
    public function destroy(Request $request, string $product_id): JsonResponse
    {
        return response()->json(['liked' => Like::setForProduct($request->user()->id, $product_id, false)]);
    }
}
