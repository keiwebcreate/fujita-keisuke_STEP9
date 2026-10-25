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
    public function create(): View
    {
        return view('contact');
    }

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
