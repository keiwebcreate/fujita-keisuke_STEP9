<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('mypage.account_edit', ['user' => $request->user()]);
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        try {
            $request->user()->updateAccount($request->validated());
        } catch (Throwable $exception) {
            report($exception);

            return to_route('account.edit')->withInput($request->only(['name', 'email', 'name_kanji', 'name_kana']))
                ->withErrors(['account' => 'アカウント情報を更新できませんでした。時間をおいて再度お試しください。']);
        }

        return to_route('mypage.show')->with('status', 'アカウント情報を更新しました。');
    }
}
