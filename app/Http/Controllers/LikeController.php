<?php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function store(Request $request, string $product_id): JsonResponse
    {
        return response()->json(['liked' => Like::setForProduct($request->user()->id, $product_id, true)]);
    }

    public function destroy(Request $request, string $product_id): JsonResponse
    {
        return response()->json(['liked' => Like::setForProduct($request->user()->id, $product_id, false)]);
    }
}
