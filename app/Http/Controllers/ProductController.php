<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        return view('products.index', [
            'products' => Product::getListedProductsForUser($request->user()->id),
        ]);
    }

    public function show(string $product_id): View
    {
        return view('products.show', ['product' => Product::getProductDetail($product_id)]);
    }
}
