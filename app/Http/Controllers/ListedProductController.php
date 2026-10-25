<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ListedProductController extends Controller
{
    public function create(Request $request): View
    {
        return view('products.create', ['can_list' => $request->user()->company_id !== null]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $image_path = null;

        try {
            $image_path = $request->file('image')->store('products', 'public');
            if ($image_path === false) {
                throw new RuntimeException('商品画像を保存できませんでした。');
            }

            Product::registerProduct($request->user(), $request->safe()->only([
                'product_name', 'description', 'price', 'stock',
            ]), $image_path);
        } catch (Throwable $exception) {
            if (is_string($image_path)) {
                Storage::disk('public')->delete($image_path);
            }
            report($exception);

            return back()->withInput($request->only(['product_name', 'description', 'price', 'stock']))
                ->withErrors(['registration' => '商品を登録できませんでした。時間をおいて再度お試しください。画像は再選択してください。']);
        }

        return to_route('mypage.show')->with('status', '商品を登録しました。');
    }

    public function show(Request $request, string $product_id): View
    {
        $product = Product::getProductDetail($product_id);
        abort_unless($product->user_id === $request->user()->id, 403);

        return view('products.listed_show', ['product' => $product]);
    }
}
