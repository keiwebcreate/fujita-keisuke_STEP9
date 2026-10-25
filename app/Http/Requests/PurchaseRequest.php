<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    /**
     * この操作をしてよい人か確認します。入力値が正しいかの確認とは別の役割です。
     * 商品が存在することと、購入者自身の出品ではないことをモデルで確認します。
     */
    public function authorize(): bool
    {
        Product::getPurchasableProduct((string) $this->route('product_id'), $this->user()->id);

        return true;
    }

    /**
     * 購入数量が1以上の半角数字の整数かを、画面とAPIで同じように確認します。
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1', 'max:2147483647', 'regex:/\A[0-9]+\z/']];
    }

    /**
     * 入力エラーを、利用者が読める日本語の案内にします。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['quantity.*' => '数量は1〜2147483647の半角数字の整数で入力してください。'];
    }
}
