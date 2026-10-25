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
    public function create(Request $request, string $product_id): View
    {
        return view('products.purchase', [
            'product' => Product::getPurchasableProduct($product_id, $request->user()->id),
        ]);
    }

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
