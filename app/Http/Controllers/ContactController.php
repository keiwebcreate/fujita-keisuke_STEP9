<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    /**
     * お問い合わせの入力画面を表示します。
     */
    public function create(): View
    {
        return view('contact');
    }

    /**
     * チェック済みの入力からメールを作り、設定された宛先へ送信します。
     * 失敗はログに記録し、利用者には入力を残した画面と案内を返します。
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        $data = $request->validated();
        try {
            Mail::to(config('mail.contact_to'))->send(new ContactMail($data['name'], $data['email'], $data['message']));
        } catch (Throwable $exception) {
            report($exception);

            return to_route('contact.create')->withInput($request->only(['name', 'email', 'message']))
                ->withErrors(['sending' => 'お問い合わせを送信できませんでした。時間をおいて再度お試しください。']);
        }

        return to_route('products.index')->with('status', 'お問い合わせを送信しました。');
    }
}
