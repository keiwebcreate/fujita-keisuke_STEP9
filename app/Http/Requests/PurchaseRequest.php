<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        Product::getPurchasableProduct((string) $this->route('product_id'), $this->user()->id);

        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1', 'max:2147483647', 'regex:/\A[0-9]+\z/']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['quantity.*' => '数量は1〜2147483647の半角数字の整数で入力してください。'];
    }
}
