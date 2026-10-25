<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchProductRequest;
use App\Models\Like;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(SearchProductRequest $request): View|JsonResponse
    {
        $filters = $request->validated();
        $products = Product::getListedProductsForUser($request->user()->id, $filters);
        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('products.product_list', ['products' => $products])->render(),
                'count' => $products->count(),
            ]);
        }

        return view('products.index', ['products' => $products, 'filters' => $filters]);
    }

    public function show(Request $request, string $product_id): View
    {
        $product = Product::getProductDetail($product_id);

        return view('products.show', [
            'product' => $product,
            'liked' => Like::isRegisteredForProduct($request->user()->id, $product->id),
        ]);
    }
}
