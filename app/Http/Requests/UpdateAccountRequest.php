<?php

namespace App\Http\Requests;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends RegisterRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['password']);
        $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)];

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'ユーザー名', 'email' => 'Eメール', 'name_kanji' => '名前（漢字）', 'name_kana' => '名前（カナ）'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => ':attributeは必須です。',
            'string' => ':attributeは文字列で入力してください。',
            'max' => ':attributeは255文字以内で入力してください。',
            'name.regex' => 'ユーザー名は半角英数字・全角文字・空白で入力してください。',
            'name_kanji.regex' => '名前（漢字）は全角文字と区切り空白で入力してください。',
            'name_kana.regex' => '名前（カナ）は全角カタカナ・長音・区切り空白で入力してください。',
            'email.email' => '有効なEメールを入力してください。',
            'email.lowercase' => 'Eメールは小文字で入力してください。',
            'email.unique' => 'このEメールは既に登録されています。',
        ];
    }
}
