<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMail extends Mailable
{
    /**
     * メール本文に渡す名前・返信先メール・問い合わせ内容を受け取ります。
     * readonlyは、一度受け取った値を後から書き換えない指定です。
     */
    public function __construct(
        public readonly string $contactName,
        public readonly string $contactEmail,
        public readonly string $contactBody,
    ) {}

    /**
     * 差出人はアプリの設定で固定し、利用者のメールは返信先（Reply-To）へ設定します。
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: [new Address($this->contactEmail)],
            subject: 'Cytech EC お問い合わせ',
        );
    }

    /**
     * メール本文を組み立てるBladeファイルを指定します。
     */
    public function content(): Content
    {
        return new Content(view: 'mail.contact');
    }
}
