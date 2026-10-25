<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * ログイン画面を表示します。
     *
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * メールとパスワードを確認し、成功したらセッション（ログイン状態を覚える仕組み）のIDを作り直します。
     * 以前のIDを使い回す危険を減らし、商品一覧へ移動します。
     *
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return to_route('products.index');
    }

    /**
     * ログイン状態を終了し、セッションと不正送信防止用の合言葉も作り直します。
     *
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return to_route('login');
    }
}
