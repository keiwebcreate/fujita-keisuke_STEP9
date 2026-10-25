<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * この操作をしてよい人か確認します。入力値が正しいかの確認とは別の役割です。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 名前の文字種、メールの重複、パスワードの長さなどを登録前に確認します。
     * confirmedは、パスワードと確認用入力が同じか調べる指定です。
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/\A[A-Za-z0-9 \p{Han}\p{Hiragana}ァ-ヺー々〆〇\x{FF01}-\x{FF60}\x{FFE0}-\x{FFE6}　]+\z/u'],
            'name_kanji' => ['required', 'string', 'max:255', 'regex:/\A[\p{Han}\p{Hiragana}ァ-ヺー々〆〇\x{FF01}-\x{FF60}\x{FFE0}-\x{FFE6}　 ]+\z/u'],
            'name_kana' => ['required', 'string', 'max:255', 'regex:/\A[ァ-ヺー・　 ]+\z/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'min:8', 'max:72', 'regex:/\A[A-Za-z0-9]+\z/', 'confirmed'],
        ];
    }
}
