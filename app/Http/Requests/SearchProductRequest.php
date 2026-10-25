<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SearchProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'product_name' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'integer', 'min:0', 'max:2147483647', 'regex:/\A[0-9]+\z/'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:2147483647', 'regex:/\A[0-9]+\z/'],
        ];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->has('min_price') && ! $validator->errors()->has('max_price')
                && $this->filled('min_price') && $this->filled('max_price')
                && (int) $this->input('min_price') > (int) $this->input('max_price')) {
                $validator->errors()->add('max_price', '最高価格は最低価格以上で入力してください。');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'product_name.string' => '商品名は文字列で入力してください。',
            'product_name.max' => '商品名は255文字以内で入力してください。',
            'min_price.*' => '最低価格は0〜2147483647の半角数字の整数で入力してください。',
            'max_price.*' => '最高価格は0〜2147483647の半角数字の整数で入力してください。',
        ];
    }
}
