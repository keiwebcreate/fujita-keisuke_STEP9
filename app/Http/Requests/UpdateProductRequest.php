<?php

namespace App\Http\Requests;

use App\Models\Product;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        Product::getOwnedProductDetail((string) $this->route('product_id'), $this->user()->id);

        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [];
    }
}
