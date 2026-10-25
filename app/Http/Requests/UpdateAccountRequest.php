<?php

namespace App\Http\Requests;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends RegisterRequest
{
    /**
     * この操作をしてよい人か確認します。入力値が正しいかの確認とは別の役割です。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 登録時のルールを受け継ぎ、パスワードの変更を対象外にします。
     * メール重複チェックから本人のIDを除くので、自分のメールを変えずに保存できます。
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['password']);
        $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)];

        return $rules;
    }

    /**
     * プログラム用の項目名を、エラー表示用の日本語に置き換えます。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'ユーザー名', 'email' => 'Eメール', 'name_kanji' => '名前（漢字）', 'name_kana' => '名前（カナ）'];
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
