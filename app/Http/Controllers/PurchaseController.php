<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PurchaseController extends Controller
{
    /**
     * 購入できる相手の商品を取り出し、数量入力画面を表示します。
     */
    public function create(Request $request, string $product_id): View
    {
        return view('products.purchase', [
            'product' => Product::getPurchasableProduct($product_id, $request->user()->id),
        ]);
    }

    /**
     * チェック済みの数量で購入処理を呼びます。
     * 在庫不足なら入力数量を残して戻し、それ以外の操作不可や商品なしの結果はそのまま伝えます。
     */
    public function store(PurchaseRequest $request, string $product_id): RedirectResponse
    {
        try {
            Sale::purchase($request->user()->id, $product_id, (int) $request->validated('quantity'));
        } catch (HttpExceptionInterface $exception) {
            if ($exception->getStatusCode() !== 409) {
                throw $exception;
            }

            return to_route('purchases.create', $product_id)->withInput($request->only('quantity'))
                ->withErrors(['quantity' => '在庫が不足しています。最新の残り在庫を確認してください。']);
        } catch (Throwable $exception) {
            report($exception);

            return to_route('purchases.create', $product_id)->withInput($request->only('quantity'))
                ->withErrors(['purchase' => '購入できませんでした。時間をおいて再度お試しください。']);
        }

        return to_route('products.index')->with('status', '商品を購入しました。');
    }
}
