<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMessageReceived extends Mailable
{
    public function __construct(public Message $contact) {}

    public function envelope(): Envelope
    {
        // Reply-To is the sender, so hitting reply in the inbox answers them directly.
        return new Envelope(
            replyTo: [new Address($this->contact->email)],
            subject: 'Portfolio: '.$this->contact->subject,
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.contact-message');
    }
}
