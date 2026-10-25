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
    /**
     * 商品登録画面を表示します。会社未設定なら登録できないことを画面へ伝えます。
     */
    public function create(Request $request): View
    {
        return view('products.create', ['can_list' => $request->user()->company_id !== null]);
    }

    /**
     * 画像を保存してから商品を登録します。
     * 商品登録に失敗した場合は、先に保存した画像も消して不要ファイルを残さないようにします。
     */
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

    /**
     * 本人の出品商品だけを取り出し、出品者向けの詳細画面へ渡します。
     */
    public function show(Request $request, string $product_id): View
    {
        $product = Product::getOwnedProductDetail($product_id, $request->user()->id);

        return view('products.listed_show', ['product' => $product]);
    }

    /**
     * 本人の商品であることを確認して、編集画面を表示します。
     */
    public function edit(Request $request, string $product_id): View
    {
        return view('products.edit', [
            'product' => Product::getOwnedProductDetail($product_id, $request->user()->id),
        ]);
    }

    /**
     * 新しい画像があれば保存し、モデルに商品更新を依頼します。
     * 失敗時は新画像、成功時は不要になった旧画像を片付けるので、使っている画像を先に消しません。
     */
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

    /**
     * モデルに論理削除を依頼し、成功したらマイページへ戻します。
     */
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

    /**
     * このアプリの商品画像という形式に合う保存先だけを削除します。
     * 画像の片付けに失敗しても、既に成功した商品更新は取り消さずログへ記録します。
     */
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
