<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
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

    public function show(string $product_id): View
    {
        return view('products.show', ['product' => Product::getProductDetail($product_id)]);
    }
}
