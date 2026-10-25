<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
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
        $product = Product::getOwnedProductDetail($product_id, $request->user()->id);

        return view('products.listed_show', ['product' => $product]);
    }

    public function edit(Request $request, string $product_id): View
    {
        return view('products.edit', [
            'product' => Product::getOwnedProductDetail($product_id, $request->user()->id),
        ]);
    }

    public function update(UpdateProductRequest $request, string $product_id): RedirectResponse
    {
        $product = Product::getOwnedProductDetail($product_id, $request->user()->id);
        $image_path = null;
        try {
            if ($request->hasFile('image')) {
                $image_path = $request->file('image')->store('products', 'public');
                if ($image_path === false) {
                    throw new RuntimeException('商品画像を保存できませんでした。');
                }
            }
            $old_image_path = $product->updateForOwner($request->user()->id, $request->safe()->only([
                'product_name', 'description', 'price', 'stock',
            ]), $image_path);
        } catch (Throwable $exception) {
            if (is_string($image_path)) {
                $this->removeImage($image_path);
            }
            report($exception);

            return to_route('listed_products.edit', $product_id)
                ->withInput($request->only(['product_name', 'description', 'price', 'stock']))
                ->withErrors(['update' => '商品を更新できませんでした。時間をおいて再度お試しください。画像を変更する場合は再選択してください。']);
        }
        if ($old_image_path !== null) {
            $this->removeImage($old_image_path);
        }

        return to_route('listed_products.show', $product_id)->with('status', '商品を更新しました。');
    }

    public function destroy(Request $request, string $product_id): RedirectResponse
    {
        $product = Product::getOwnedProductDetail($product_id, $request->user()->id);
        try {
            $product->deleteForOwner($request->user()->id);
        } catch (Throwable $exception) {
            report($exception);

            return to_route('listed_products.show', $product_id)
                ->withErrors(['deletion' => '商品を削除できませんでした。時間をおいて再度お試しください。']);
        }

        return to_route('mypage.show')->with('status', '商品を削除しました。');
    }

    private function removeImage(string $image_path): void
    {
        if (preg_match('/\Aproducts\/[A-Za-z0-9_-]+\.(?:jpe?g|png|webp)\z/i', $image_path) !== 1) {
            return;
        }
        try {
            if (! Storage::disk('public')->delete($image_path)) {
                throw new RuntimeException('不要になった商品画像を削除できませんでした。');
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
