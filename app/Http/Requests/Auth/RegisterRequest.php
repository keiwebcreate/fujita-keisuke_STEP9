<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
            'name' => ['required', 'string', 'max:255'],
            'name_kanji' => ['required', 'string', 'max:255', 'regex:/\A[\p{Han}\p{Hiragana}ァ-ヺー々〆〇\x{FF01}-\x{FF60}\x{FFE0}-\x{FFE6}　 ]+\z/u'],
            'name_kana' => ['required', 'string', 'max:255', 'regex:/\A[ァ-ヺー・　 ]+\z/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
