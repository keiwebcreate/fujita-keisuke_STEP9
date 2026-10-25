<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * この操作をしてよい人か確認します。入力値が正しいかの確認とは別の役割です。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 名前・メール・本文の必須項目と長さを確認します。
     * regexは「使ってよい文字の決まり」です。半角カナや絵文字は通さず、本文だけ改行・タブも許可します。
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/\A[\x20-\x7E\p{Han}\p{Hiragana}ァ-ヺー々〆〇\x{3000}-\x{303F}\x{FF01}-\x{FF60}\x{FFE0}-\x{FFE6}]+\z/u'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000', 'regex:/\A[\x20-\x7E\p{Han}\p{Hiragana}ァ-ヺー々〆〇\x{3000}-\x{303F}\x{FF01}-\x{FF60}\x{FFE0}-\x{FFE6}\r\n\t]+\z/u'],
        ];
    }

    /**
     * プログラム用の項目名を、エラー表示用の日本語に置き換えます。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => '名前', 'email' => 'メールアドレス', 'message' => 'お問い合わせ内容'];
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
            'string' => ':attributeは文字列で入力してください。',
            'name.max' => '名前は255文字以内で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'name.regex' => '名前は半角英数字・記号・全角文字・空白で入力してください。',
            'message.regex' => 'お問い合わせ内容は半角英数字・記号・全角文字・空白・改行で入力してください。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'message.max' => 'お問い合わせ内容は2000文字以内で入力してください。',
        ];
    }
}
