<?php

namespace App\Http\Requests;

use App\Models\Product;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * この操作をしてよい人か確認します。入力値が正しいかの確認とは別の役割です。
     * 本人の出品商品以外は編集できないよう、モデルで確認します。
     */
    public function authorize(): bool
    {
        Product::getOwnedProductDetail((string) $this->route('product_id'), $this->user()->id);

        return true;
    }

    /**
     * 商品登録と同じ条件を使い、画像だけは未選択でもよいルールに上書きします。
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    /**
     * 編集では、登録用の会社未設定チェックを受け継がず、空の追加チェックを返します。
     *
     * @return array<callable>
     */
    public function after(): array
    {
        return [];
    }
}
