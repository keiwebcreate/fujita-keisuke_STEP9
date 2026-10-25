<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    /**
     * この操作をしてよい人か確認します。入力値が正しいかの確認とは別の役割です。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 商品名・説明・価格・在庫・画像の入力条件を定義します。
     * requiredは必須、maxは上限、regexは許可文字の決まりです。画像のmax:2048は2MiBを表します。
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255', 'regex:/\A[A-Za-z0-9 \p{Han}\p{Hiragana}ァ-ヺー々〆〇\x{FF01}-\x{FF60}\x{FFE0}-\x{FFE6}　]+\z/u'],
            'description' => ['required', 'string', 'max:255', 'regex:/\A[A-Za-z0-9 \r\n\p{Han}\p{Hiragana}ァ-ヺー々〆〇\x{FF01}-\x{FF60}\x{FFE0}-\x{FFE6}　]+\z/u'],
            'price' => ['required', 'integer', 'min:0', 'max:2147483647', 'regex:/\A[0-9]+\z/'],
            'stock' => ['required', 'integer', 'min:0', 'max:2147483647', 'regex:/\A[0-9]+\z/'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * 文字や数値のチェックに加え、出品者に所属会社が設定されているか確認します。
     *
     * @return array<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->user()->company_id === null) {
                $validator->errors()->add('company', '所属会社が未設定のため商品を登録できません。管理者に会社の設定を依頼してください。');
            }
        }];
    }

    /**
     * プログラム用の項目名を、エラー表示用の日本語に置き換えます。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['product_name' => '商品名', 'description' => '商品説明', 'price' => '価格', 'stock' => '在庫数', 'image' => '商品画像'];
    }

    /**
     * 入力エラーを、利用者が読める日本語の案内にします。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attributeは必須です。',
            'product_name.regex' => '商品名は半角英数字・全角文字・空白で入力してください。',
            'description.regex' => '商品説明は半角英数字・全角文字・空白・改行で入力してください。',
            'product_name.max' => '商品名は255文字以内で入力してください。',
            'description.max' => '商品説明は255文字以内で入力してください。',
            'price.*' => '価格は0〜2147483647の半角数字の整数で入力してください。',
            'stock.*' => '在庫数は0〜2147483647の半角数字の整数で入力してください。',
            'image.image' => '商品画像には画像ファイルを選択してください。',
            'image.mimes' => '商品画像はJPEG・PNG・WebP形式で選択してください。',
            'image.max' => '商品画像は2MiB以下で選択してください。',
        ];
    }
}
