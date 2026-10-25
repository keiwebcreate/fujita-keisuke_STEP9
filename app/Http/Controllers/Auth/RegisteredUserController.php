<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * 新規利用者の登録画面を表示します。
     *
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * 入力チェック済みの情報を登録し、登録完了イベントを通知します。
     * 登録後は自動ログインせず、ログイン画面へ戻します。
     *
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::registerUser($request->validated());

        event(new Registered($user));

        return to_route('login')->with('status', '登録が完了しました。ログインしてください。');
    }
}
