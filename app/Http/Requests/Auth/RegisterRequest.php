<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
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
