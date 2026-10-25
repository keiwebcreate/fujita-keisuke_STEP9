<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMail extends Mailable
{
    public function __construct(
        public readonly string $contactName,
        public readonly string $contactEmail,
        public readonly string $contactBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: [new Address($this->contactEmail)],
            subject: 'Cytech EC お問い合わせ',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact');
    }
}
