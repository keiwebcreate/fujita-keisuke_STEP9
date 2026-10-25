<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => '名前', 'email' => 'メールアドレス', 'message' => 'お問い合わせ内容'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => ':attributeは必須です。',
            'string' => ':attributeは文字列で入力してください。',
            'name.max' => '名前は255文字以内で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'message.max' => 'お問い合わせ内容は2000文字以内で入力してください。',
        ];
    }
}
